<?php

namespace App\Livewire;

use App\Services\CryptoService;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Livewire\Component;
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Scanner\CodeScanned;
use Native\Mobile\Facades\Scanner;
use Native\Mobile\Facades\SecureStorage;

class KeyTransfer extends Component
{
    public ?string $qrDataUri = null;
    public bool $confirmReplace = false;
    public ?string $pendingKey = null;
    public bool $importSuccess = false;
    public string $error = '';

    private const SCAN_ID = 'key-transfer';

    public function showQr(): void
    {
        $this->error = '';
        $privatePem = SecureStorage::get('device_private_key');

        if ($privatePem === null) {
            // Boot-time key generation may have failed silently — try now
            try {
                $pair = app(CryptoService::class)->generateKeyPair();
                SecureStorage::set('device_private_key', $pair['private']);
                SecureStorage::set('device_public_key', $pair['public']);
                $privatePem = $pair['private'];
            } catch (\Throwable) {
                $this->error = 'Impossible de générer les clés. Veuillez relancer l\'application.';
                return;
            }
        }

        $qr = new QrCode(
            data: $privatePem,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 300,
            margin: 10,
        );

        $result = (new PngWriter())->write($qr);
        $this->qrDataUri = 'data:image/png;base64,' . base64_encode($result->getString());
    }

    public function startScan(): void
    {
        $this->error = '';
        Scanner::scan()
            ->prompt('Scannez le QR code de votre ancien appareil')
            ->formats(['qr'])
            ->id(self::SCAN_ID)
            ->open();
    }

    #[OnNative(CodeScanned::class)]
    public function handleScan(string $data, string $format, ?string $id = null): void
    {
        if ($id !== self::SCAN_ID) {
            return;
        }

        $existingKey = SecureStorage::get('device_private_key');

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

    private function storeScannedKey(string $privatePem): void
    {
        $privKey = openssl_pkey_get_private($privatePem);
        if ($privKey === false) {
            $this->error = 'Clé invalide — le QR code ne contient pas une clé valide.';
            return;
        }
        $details   = openssl_pkey_get_details($privKey);
        $publicPem = $details['key'];

        SecureStorage::set('device_private_key', $privatePem);
        SecureStorage::set('device_public_key', $publicPem);

        $this->importSuccess = true;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.key-transfer')
            ->layout('layouts.app', ['title' => 'Transfert de clés']);
    }
}
