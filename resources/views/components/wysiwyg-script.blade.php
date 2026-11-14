@once
@push('scripts')
<script>
    (function () {
        function initOne(el) {
            if (!el || el.dataset.wysiwygInit) return;
            if (window.tinymce && window.tinymce.get && window.tinymce.get(el.id)) return;
            if (!el.id) el.id = 'wysiwyg-' + Math.random().toString(36).slice(2);
            el.dataset.wysiwygInit = '1';
            window.tinymce.init({
                target: el,
                height: 320,
                menubar: false,
                skin_url: '/tinymce/skins/ui/oxide',
                content_css: '/tinymce/skins/content/default',
                plugins: 'lists link image table code autolink fullscreen media',
                toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link table | removeformat code fullscreen',
                branding: false,
                promotion: false,
                relative_urls: false,
                block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Preformatted=pre',
            });
        }

        window.initAllWysiwyg = window.initAllWysiwyg || function () {
            if (!window.tinymce) return;
            document.querySelectorAll('textarea.cms-wysiwyg:not([data-wysiwyg-init])').forEach(initOne);
        };

        if (window.tinymce) window.initAllWysiwyg();
        document.addEventListener('livewire:navigated', function () {
            if (window.tinymce && typeof window.initAllWysiwyg === 'function') window.initAllWysiwyg();
        });
    })();
</script>
@endpush
@endonce
