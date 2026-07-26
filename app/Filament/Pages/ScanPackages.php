<?php

namespace App\Filament\Pages;

use App\Enums\PackageStatus;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PackageScanService;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * @property Schema $form
 */
class ScanPackages extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static ?string $navigationLabel = 'مسح الطرود';

    protected static ?string $title = 'مسح وصول طرد';

    protected static ?string $slug = 'scan-packages';

    protected static string|UnitEnum|null $navigationGroup = 'العمليات';

    protected static ?int $navigationSort = 2;

    public ?array $data = [];

    public ?string $lastReference = null;

    public ?string $lastBarcode = null;

    public ?string $lastStatus = null;

    public ?string $lastRecipient = null;

    public ?string $lastPackageProgress = null;

    public ?string $lastWeight = null;

    public ?string $lastPaymentSummary = null;

    public function mount(): void
    {
        $user = auth()->user();

        $this->form->fill([
            'warehouse_id' => $user instanceof User ? $user->warehouse_id : null,
        ]);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isAdministrator() || $user->warehouse_id !== null);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الوصول')
                    ->description('امسح باركود النظام ثم ثبّت وصول الطرد إلى مستودعك.')
                    ->schema([
                        Select::make('warehouse_id')
                            ->label('مستودع المسح')
                            ->options(function (): array {
                                $user = auth()->user();

                                if (! $user instanceof User) {
                                    return [];
                                }

                                return Warehouse::query()
                                    ->visibleTo($user)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->required()
                            ->disabled(fn (): bool => ! auth()->user()?->isAdministrator())
                            ->dehydrated(),

                        TextInput::make('barcode')
                            ->label('الباركود')
                            ->placeholder('PKG-XXXXXXXXXX')
                            ->required()
                            ->maxLength(255)
                            ->autofocus()
                            ->extraInputAttributes([
                                'dir' => 'ltr',
                                'autocomplete' => 'off',
                                'autocapitalize' => 'none',
                                'style' => 'font-size:1.125rem;text-align:left',
                            ]),

                        Html::make(new HtmlString(<<<'HTML'
                            <div
                                class="rounded-xl border border-gray-200 p-3 dark:border-white/10"
                                x-data="{
                                    active: false,
                                    stream: null,
                                    async start() {
                                        if (!('BarcodeDetector' in window) || !navigator.mediaDevices?.getUserMedia) {
                                            alert('الكاميرا غير مدعومة في هذا المتصفح. اكتب الباركود أو استخدم قارئاً خارجياً.');
                                            return;
                                        }

                                        try {
                                            this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                                            this.$refs.video.srcObject = this.stream;
                                            await this.$refs.video.play();
                                            this.active = true;
                                            const detector = new BarcodeDetector();

                                            while (this.active) {
                                                const codes = await detector.detect(this.$refs.video);
                                                if (codes.length) {
                                                    $wire.set('data.barcode', codes[0].rawValue);
                                                    this.stop();
                                                    break;
                                                }
                                                await new Promise(resolve => setTimeout(resolve, 250));
                                            }
                                        } catch (error) {
                                            this.stop();
                                            alert('تعذّر فتح الكاميرا. تحقق من الإذن ثم حاول مجدداً.');
                                        }
                                    },
                                    stop() {
                                        this.active = false;
                                        this.stream?.getTracks().forEach(track => track.stop());
                                        this.stream = null;
                                    }
                                }"
                            >
                                <button
                                    type="button"
                                    class="fi-btn fi-btn-size-md fi-color fi-color-primary w-full justify-center"
                                    x-on:click="active ? stop() : start()"
                                    x-text="active ? 'إيقاف الكاميرا' : 'فتح كاميرا الهاتف'"
                                ></button>
                                <video
                                    x-ref="video"
                                    x-show="active"
                                    class="mt-3 aspect-video w-full rounded-lg bg-black object-cover"
                                    playsinline
                                    muted
                                ></video>
                            </div>
                            HTML)),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('scan-form')
                ->livewireSubmitHandler('scan')
                ->footer([
                    Actions::make([
                        Action::make('scan')
                            ->label('تسجيل الوصول')
                            ->icon(Heroicon::OutlinedQrCode)
                            ->submit('scan'),
                    ])->fullWidth(),
                ]),

            Section::make('نتيجة المسح')
                ->visible(fn (): bool => $this->lastReference !== null)
                ->schema([
                    TextEntry::make('lastReference')
                        ->label('رقم الشحنة')
                        ->state(fn (): ?string => $this->lastReference),
                    TextEntry::make('lastBarcode')
                        ->label('باركود الطرد')
                        ->state(fn (): ?string => $this->lastBarcode),
                    TextEntry::make('lastStatus')
                        ->label('الحالة')
                        ->badge()
                        ->state(fn (): ?string => $this->lastStatus),
                    TextEntry::make('lastRecipient')
                        ->label('المستلم')
                        ->state(fn (): ?string => $this->lastRecipient),
                    TextEntry::make('lastPackageProgress')
                        ->label('تقدم الطرود')
                        ->state(fn (): ?string => $this->lastPackageProgress),
                    TextEntry::make('lastWeight')
                        ->label('الوزن المتوقع')
                        ->state(fn (): ?string => $this->lastWeight),
                    TextEntry::make('lastPaymentSummary')
                        ->label('ملخص الدفع')
                        ->state(fn (): ?string => $this->lastPaymentSummary),
                ])
                ->columns(1),
        ]);
    }

    public function scan(PackageScanService $service): void
    {
        $state = $this->form->getState();
        $user = auth()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $warehouse = Warehouse::findOrFail($state['warehouse_id']);
        try {
            $package = $service->scan(
                $state['barcode'],
                $warehouse,
                $user,
                'camera_or_scanner',
            );
        } catch (DomainException $exception) {
            // Operational refusals belong beside the scanned barcode, not on
            // a generic error page that forces a phone operator to start over.
            $this->addError('data.barcode', $exception->getMessage());

            return;
        }

        $shipment = $package->shipment;
        $activePackages = $shipment->packages()
            ->where('status', '!=', PackageStatus::Cancelled->value);
        $activeCount = (clone $activePackages)->count();
        $arrivedCount = (clone $activePackages)
            ->whereIn('status', [
                PackageStatus::ArrivedDestination->value,
                PackageStatus::Collected->value,
            ])
            ->count();

        $this->lastReference = $shipment->reference;
        $this->lastBarcode = $package->barcode;
        $this->lastStatus = $package->status->label();
        $this->lastRecipient = $shipment->recipient_name;
        $this->lastPackageProgress = "{$arrivedCount} من {$activeCount}";
        $this->lastWeight = "{$shipment->total_weight_kg} كغ";
        $this->lastPaymentSummary = sprintf(
            'المطلوب: %s، المدفوع: %s، المتبقي: %s',
            $this->formatCents((int) $shipment->final_charge_cents),
            $this->formatCents((int) $shipment->paid_amount_cents),
            $this->formatCents($shipment->outstandingCents()),
        );

        $this->form->fill([
            'warehouse_id' => $warehouse->id,
            'barcode' => null,
        ]);

        Notification::make()
            ->title('تم تسجيل وصول الطرد بنجاح.')
            ->success()
            ->send();
    }

    private function formatCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return sprintf(
            '%s%d.%02d $',
            $sign,
            intdiv($absolute, 100),
            $absolute % 100,
        );
    }
}
