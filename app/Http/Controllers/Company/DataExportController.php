<?php

namespace App\Http\Controllers\Company;

use App\Exports\CompanyDataWorkbook;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\CompanyDataExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class DataExportController extends Controller
{
    /**
     * صفحة "بياناتي" داخل لوحة الشركة (زر التنزيل + شرح).
     */
    public function index()
    {
        return view('company.data-export.index');
    }

    /**
     * تنزيل كامل بيانات الشركة كملف ZIP يحتوي:
     *   - company-data.xlsx : شيت لكل كيان (للقراءة البشرية)
     *   - company-data.json : نسخة كاملة قابلة لإعادة الاستيراد
     *   - README.txt        : شرح المحتوى
     */
    public function download(): StreamedResponse
    {
        /** @var Company $company */
        $company = Auth::guard('company')->user();

        $exporter  = new CompanyDataExporter($company);
        $collected = $exporter->collect();
        $payload   = $exporter->toJsonPayload();

        $xlsx = Excel::raw(new CompanyDataWorkbook($collected), \Maatwebsite\Excel\Excel::XLSX);
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $zipPath = tempnam(sys_get_temp_dir(), 'company-export-');

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::OVERWRITE);
        $zip->addFromString('company-data.xlsx', $xlsx);
        $zip->addFromString('company-data.json', $json);
        $zip->addFromString('README.txt', $this->readme($company, $collected));
        $zip->close();

        $fileName = 'booksy-data-' . $company->id . '-' . now()->format('Y-m-d') . '.zip';

        return response()->streamDownload(function () use ($zipPath) {
            readfile($zipPath);
            @unlink($zipPath);
        }, $fileName, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * إغلاق الحساب (Soft delete) — البيانات تُحفظ كاملة وقابلة للاستعادة
     * من قِبل الأونر. يتطلب تأكيد كلمة المرور.
     */
    public function close(Request $request)
    {
        /** @var Company $company */
        $company = Auth::guard('company')->user();

        $request->validate([
            'password' => ['required', 'string'],
            'reason'   => ['nullable', 'string', 'max:500'],
        ]);

        if (! Hash::check($request->input('password'), $company->password)) {
            return back()->withErrors([
                'password' => __('The password is incorrect.'),
            ]);
        }

        $company->closure_reason = $request->input('reason');
        $company->save();
        $company->delete(); // soft delete — deleted_at فقط، بدون مسح الشعار أو البيانات

        Auth::guard('company')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('company.login')
            ->with('status', __('Your account has been closed. Your data is safely kept — contact support to reactivate it.'));
    }

    /**
     * @param  array<string, array{label:string, rows:array}>  $collected
     */
    private function readme(Company $company, array $collected): string
    {
        $lines = [
            'نسخة بيانات ' . ($company->name ?? ('#' . $company->id)),
            'تاريخ التصدير: ' . now()->format('Y-m-d H:i'),
            str_repeat('-', 40),
            'الملفات:',
            '  • company-data.xlsx — بياناتك مقروءة، شيت لكل قسم.',
            '  • company-data.json — نسخة كاملة تُستخدم لإعادة الاستيراد.',
            str_repeat('-', 40),
            'الأقسام المصدَّرة وعدد السجلات:',
        ];

        foreach ($collected as $entity) {
            $lines[] = '  • ' . ($entity['label'] ?? '') . ': ' . count($entity['rows'] ?? []);
        }

        return implode("\n", $lines) . "\n";
    }
}
