$branches = App\Models\Branch::all();
foreach ($branches as $b) {
    try {
        $p = (new App\Services\BranchQrService())->generate($b);
        $b->update(['qr_code' => $p]);
        echo "OK: " . $b->id . "\n";
    } catch (Throwable $e) {
        echo "ERR " . $b->id . ": " . $e->getMessage() . "\n";
    }
}