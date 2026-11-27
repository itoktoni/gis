<?php

namespace App\Http\Controllers;

use App\Models\WebsiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class WebsiteSettingController extends Controller
{
    public function index(): View
    {
        $settings = config('website');

        return view('pages.settings.website', [
            'settings' => $settings,
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'alamat' => ['nullable', 'string'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:512'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_favicon' => ['nullable', 'boolean'],
            'primary_color' => ['nullable', 'string', 'max:7'],
            'footer_text' => ['nullable', 'string'],
            'report_header_image' => ['nullable', 'image', 'max:2048'],
            'report_header_left' => ['nullable', 'image', 'max:2048'],
            'report_footer_image' => ['nullable', 'image', 'max:2048'],
            'remove_report_header' => ['nullable', 'boolean'],
            'remove_report_header_left' => ['nullable', 'boolean'],
            'remove_report_footer' => ['nullable', 'boolean'],
            'report_show_header' => ['nullable', 'boolean'],
            'report_show_footer' => ['nullable', 'boolean'],
            'report_instansi' => ['nullable', 'string', 'max:255'],
            'report_kop_alamat' => ['nullable', 'string'],
            'report_kota' => ['nullable', 'string', 'max:100'],
            'report_jabatan' => ['nullable', 'string', 'max:100'],
            'report_nama' => ['nullable', 'string', 'max:100'],
            'report_nip' => ['nullable', 'string', 'max:50'],
            'report_show_print_meta' => ['nullable', 'boolean'],
        ]);

        $dir = public_path('storage/website');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $envUpdates = [];

        // Text fields → .env
        $envUpdates['APP_NAME'] = $validated['name'];
        $envUpdates['WEBSITE_TAGLINE'] = $validated['tagline'] ?? '';
        $envUpdates['WEBSITE_DESCRIPTION'] = $validated['description'] ?? '';
        $envUpdates['WEBSITE_ALAMAT'] = $validated['alamat'] ?? '';
        $envUpdates['WEBSITE_TELEPON'] = $validated['telepon'] ?? '';
        $envUpdates['WEBSITE_EMAIL'] = $validated['email'] ?? '';
        $envUpdates['WEBSITE_FOOTER_TEXT'] = $validated['footer_text'] ?? '';

        // Gambar header & footer report → .env
        $envUpdates['REPORT_INSTANSI'] = $validated['report_instansi'] ?? '';
        $envUpdates['REPORT_KOP_ALAMAT'] = $validated['report_kop_alamat'] ?? '';
        $envUpdates['REPORT_SHOW_HEADER'] = $request->boolean('report_show_header') ? 'true' : 'false';
        $envUpdates['REPORT_SHOW_FOOTER'] = $request->boolean('report_show_footer') ? 'true' : 'false';
        $envUpdates['REPORT_KOTA'] = $validated['report_kota'] ?? '';
        $envUpdates['REPORT_JABATAN'] = $validated['report_jabatan'] ?? '';
        $envUpdates['REPORT_NAMA'] = $validated['report_nama'] ?? '';
        $envUpdates['REPORT_NIP'] = $validated['report_nip'] ?? '';
        $envUpdates['REPORT_SHOW_PRINT_META'] = $request->boolean('report_show_print_meta') ? 'true' : 'false';

        // Color → .env
        if (! empty($validated['primary_color'])) {
            $envUpdates['WEBSITE_COLOR_PRIMARY'] = $validated['primary_color'];
        }

        // Logo file upload
        if ($request->hasFile('logo')) {
            $this->deleteOld(config('website.logo'));
            $envUpdates['WEBSITE_LOGO'] = 'storage/website/'.$this->storeFile($request->file('logo'), $dir);
        } elseif (! empty($validated['remove_logo'])) {
            $this->deleteOld(config('website.logo'));
            $envUpdates['WEBSITE_LOGO'] = '';
        }

        // Favicon file upload
        if ($request->hasFile('favicon')) {
            $this->deleteOld(config('website.favicon'));
            $envUpdates['WEBSITE_FAVICON'] = 'storage/website/'.$this->storeFile($request->file('favicon'), $dir);
        } elseif (! empty($validated['remove_favicon'])) {
            $this->deleteOld(config('website.favicon'));
            $envUpdates['WEBSITE_FAVICON'] = '';
        }

        // Header report image (kiri / logo)
        if ($request->hasFile('report_header_left')) {
            $this->deleteOld(config('website.report.header_left'));
            $envUpdates['REPORT_HEADER_LEFT'] = 'storage/website/'.$this->storeFile($request->file('report_header_left'), $dir);
        } elseif (! empty($validated['remove_report_header_left'])) {
            $this->deleteOld(config('website.report.header_left'));
            $envUpdates['REPORT_HEADER_LEFT'] = '';
        }

        // Header report image (kanan / kop)
        if ($request->hasFile('report_header_image')) {
            $this->deleteOld(config('website.report.header_image'));
            $envUpdates['REPORT_HEADER_IMAGE'] = 'storage/website/'.$this->storeFile($request->file('report_header_image'), $dir);
        } elseif (! empty($validated['remove_report_header'])) {
            $this->deleteOld(config('website.report.header_image'));
            $envUpdates['REPORT_HEADER_IMAGE'] = '';
        }

        // Footer report image
        if ($request->hasFile('report_footer_image')) {
            $this->deleteOld(config('website.report.footer_image'));
            $envUpdates['REPORT_FOOTER_IMAGE'] = 'storage/website/'.$this->storeFile($request->file('report_footer_image'), $dir);
        } elseif (! empty($validated['remove_report_footer'])) {
            $this->deleteOld(config('website.report.footer_image'));
            $envUpdates['REPORT_FOOTER_IMAGE'] = '';
        }

        $this->writeToEnv($envUpdates);

        flash()->success('Website settings saved.');

        return Redirect::route('settings.website');
    }

    private function writeToEnv(array $updates): void
    {
        $path = base_path('.env');
        $content = file_get_contents($path);

        foreach ($updates as $key => $value) {
            $escaped = str_replace('"', '\\"', $value);
            $line = $key.'="'.$escaped.'"';

            if (preg_match('/^'.preg_quote($key, '/').'.*/m', $content)) {
                $content = preg_replace('/^'.preg_quote($key, '/').'.*/m', $line, $content);
            } else {
                $content .= PHP_EOL.$line.PHP_EOL;
            }
        }

        file_put_contents($path, $content);
        Artisan::call('config:clear');
    }

    private function storeFile($file, string $dir): string
    {
        $name = uniqid().'_'.preg_replace('/[^a-zA-Z0-9.]/', '', $file->getClientOriginalName());
        $file->move($dir, $name);

        return $name;
    }

    private function deleteOld(?string $path): void
    {
        if (empty($path)) {
            return;
        }

        $file = public_path($path);
        if (file_exists($file)) {
            unlink($file);
        }
    }
}
