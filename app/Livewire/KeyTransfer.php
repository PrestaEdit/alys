<?php

namespace App\Livewire;

use App\Services\CryptoService;
use Livewire\Component;
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Scanner\CodeScanned;
use Native\Mobile\Facades\Scanner;
use Native\Mobile\Facades\SecureStorage;

class KeyTransfer extends Component
{
    public ?string $qrContent = null;
    public bool $confirmReplace = false;
    public ?string $pendingKey = null;
    public bool $importSuccess = false;
    public string $error = '';

    private const SCAN_ID = 'key-transfer';

    public function showQr(): void
    {
        $this->error = '';
        $key = SecureStorage::get('device_key');

        if ($key === null) {
            try {
                $key = app(CryptoService::class)->generateKey();
                SecureStorage::set('device_key', $key);
            } catch (\Throwable) {
                $this->error = __('data.key_err_generate');
                return;
            }
        }

        $this->qrContent = $key;
    }

    public function startScan(): void
    {
        Scanner::scan()
            ->prompt(__('data.key_scan_prompt'))
            ->formats(['qr'])
            ->id(self::SCAN_ID);
    }

    #[OnNative(CodeScanned::class)]
    public function handleScan(string $data, string $format, ?string $id = null): void
    {
        if ($id !== self::SCAN_ID) {
            return;
        }

        $existingKey = SecureStorage::get('device_key');

        if ($existingKey !== null) {
            $this->pendingKey     = $data;
            $this->confirmReplace = true;
            return;
        }

        $this->storeScannedKey($data);
    }

    public function confirmReplaceKeys(): void
    {
        if ($this->pendingKey === null) {
            return;
        }

        $this->storeScannedKey($this->pendingKey);
        $this->confirmReplace = false;
        $this->pendingKey     = null;
    }

    public function cancelReplace(): void
    {
        $this->confirmReplace = false;
        $this->pendingKey     = null;
    }

    private function storeScannedKey(string $keyBase64): void
    {
        $decoded = base64_decode($keyBase64, true);
        if ($decoded === false || strlen($decoded) !== 32) {
            $this->error = __('data.key_err_invalid');
            return;
        }

        SecureStorage::set('device_key', $keyBase64);
        $this->importSuccess = true;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.key-transfer')
            ->layout('layouts.app', ['title' => __('data.key_title')]);
    }
}
