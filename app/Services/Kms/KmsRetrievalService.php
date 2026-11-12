<?php

namespace App\Services\Kms;

use App\Models\KmsChunk;
use Illuminate\Support\Collection;

/**
 * Pencarian potongan dokumen paling relevan via MySQL FULLTEXT
 * (MATCH ... AGAINST natural language) + fallback LIKE untuk kata pendek
 * (< ft_min_word_len) atau saat FULLTEXT tidak mengembalikan hasil.
 */
class KmsRetrievalService
{
    /**
     * @param  array<int, int|string>  $divisiIds  kosong = semua divisi
     * @param  array<int, int|string>  $tagIds  kosong = semua tag
     *
     * @return Collection<int, KmsChunk> chunks + relasi hasDocument + atribut relevance
     */
    public static function search(string $query, ?int $limit = null, array $divisiIds = [], array $tagIds = []): Collection
    {
        $limit = $limit ?? (int) config('kms.top_k', 5);
        $terms = self::terms($query);

        if ($terms === []) {
            return collect();
        }

        $scope = function ($q) use ($divisiIds, $tagIds) {
            $q->where('kms_document_status', 'ready')
                ->when($divisiIds !== [], fn ($qq) => $qq->whereHas('hasDivisis', fn ($qqq) => $qqq->whereIn('divisi.divisi_id', $divisiIds)))
                ->when($tagIds !== [], fn ($qq) => $qq->whereHas('hasTags', fn ($qqq) => $qqq->whereIn('cms_tags.id', $tagIds)));
        };

        $against = implode(' ', $terms);
        $found = KmsChunk::query()
            ->selectRaw('kms_chunks.*, MATCH(kms_chunk_teks) AGAINST(? IN NATURAL LANGUAGE MODE) AS relevance', [$against])
            ->whereHas('hasDocument', $scope)
            ->whereRaw('MATCH(kms_chunk_teks) AGAINST(? IN NATURAL LANGUAGE MODE)', [$against])
            ->orderByDesc('relevance')
            ->limit($limit)
            ->with('hasDocument')
            ->get();

        // Fallback LIKE: kejar sisa slot dengan pencocokan istilah satu per satu.
        if ($found->count() < $limit) {
            $exclude = $found->pluck('kms_chunk_id')->all();
            $like = KmsChunk::query()
                ->whereHas('hasDocument', $scope)
                ->when($exclude !== [], fn ($q) => $q->whereNotIn('kms_chunk_id', $exclude))
                ->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->orWhere('kms_chunk_teks', 'LIKE', '%'.$term.'%');
                    }
                })
                ->limit($limit - $found->count())
                ->with('hasDocument')
                ->get()
                ->each(fn ($c) => $c->setAttribute('relevance', 0));

            $found = $found->concat($like);
        }

        return $found->values();
    }

    /**
     * Token pencarian: huruf/angka, panjang >= 3, buang stopwords Indonesia umum.
     *
     * @return array<int, string>
     */
    public static function terms(string $query): array
    {
        $query = mb_strtolower($query);
        $query = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query) ?? '';
        $words = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $stopwords = ['yang', 'dan', 'itu', 'ini', 'apa', 'bagaimana', 'berapa', 'kapan', 'dimana', 'kenapa',
            'adalah', 'untuk', 'dengan', 'dari', 'dalam', 'pada', 'atau', 'juga', 'tidak', 'sudah', 'telah',
            'akan', 'bisa', 'saja', 'oleh', 'karena', 'sebagai', 'tentang', 'the', 'and', 'atau'];

        $terms = [];
        foreach ($words as $word) {
            if (mb_strlen($word) < 3 || in_array($word, $stopwords, true)) {
                continue;
            }
            $terms[] = $word;
        }

        return array_values(array_unique($terms));
    }
}
