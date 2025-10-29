<div class="garden-container mt-6 border border-surface/20 rounded-lg p-4 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20">
    <div class="flex items-center gap-2 mb-4">
        <span class="material-symbols-outlined text-green-600">grass</span>
        <h4 class="font-semibold text-green-800 dark:text-green-200">{{ __('jardino::garden.title') }}</h4>
    </div>

    @if($viewModel->gardenMap)
    @php
    $gardenMap = $viewModel->gardenMap;
    $gardenWidth = $gardenMap->width;
    $gardenHeight = $gardenMap->height;
    $gardenCellWidth = $gardenMap->cellWidth;
    $gardenCellHeight = $gardenMap->cellHeight;
    @endphp
    <div class="garden-grid grid overflow-scroll grid gap-0 bg-cover bg-no-repeat bg-local max-h-[90vh] overflow-scroll"
        data-width="{{ $gardenWidth }}"
        data-height="{{ $gardenHeight }}"
        data-cell-width="{{ $gardenCellWidth }}"
        data-cell-height="{{ $gardenCellHeight }}"
        x-data="flowerModal({
                 cellX: 0,
                 cellY: 0,
                 activityId: {{ $viewModel->activityId }},
                 flowersAvailable: {{ $viewModel->objective ? $viewModel->objective->flowersAvailable : 0 }},
                 currentUserId: {{ auth()->id() }},
                 isAdmin: {{ $viewModel->isAdmin ? 'true' : 'false' }}
             })"
        style="max-width: {{ $gardenWidth * $gardenCellWidth }}px; max-height: {{ $gardenHeight * $gardenCellHeight }}px; display: grid; grid-template-columns: repeat({{ $gardenWidth }}, {{ $gardenCellWidth }}px); grid-template-rows: repeat({{ $gardenHeight }}, {{ $gardenCellHeight }}px); background-image: url({{  asset('images/activities/jardino/background.png') }});">

        @for($y = 0; $y < $gardenHeight; $y++)
            @for($x = 0; $x < $gardenWidth; $x++)
                @php
                $cell = $gardenMap->getCell($x, $y);
                $isOccupied = $cell !== null;
                @endphp

                <div class="garden-cell relative cursor-pointer hover:bg-green-300 dark:hover:bg-green-700 transition-colors"
                    :class="{ 'cursor-not-allowed': {{ $isOccupied ? 'true' : 'false' }} }"
                    @click="handleCellClick($el)"
                    data-x="{{ $x }}"
                    data-y="{{ $y }}"
                    data-occupied="{{ $isOccupied ? 'true' : 'false' }}"
                    @if($isOccupied)
                    data-type="{{ $cell->type }}"
                    @if($cell->type === 'flower')
                    data-flower-image="{{ $cell->flowerImage }}"
                    data-user-id="{{ $cell->userId }}"
                    data-display-name="{{ $cell->displayName ?? '' }}"
                    data-avatar-url="{{ $cell->avatarUrl ?? '' }}"
                    @endif
                    @endif
                    style="display: flex; align-items: center; justify-content: center; font-size: 8px;">

                    @if($isOccupied && $cell->type === 'flower')
                    <img src="{{ asset('images/activities/jardino/' . $cell->flowerImage) }}"
                        alt="Flower planted by {{ $cell->displayName ?: 'user ' . $cell->userId }}"
                        class="w-full h-full object-contain"
                        style="max-width: {{ $gardenCellWidth - 2 }}px; max-height: {{ $gardenCellHeight - 2 }}px;">
                    @elseif(!$isOccupied)
                    <span class="bg-black/10 w-[50%] h-[50%] rounded-full"></span>
                    @endif
                </div>
                @endfor
                @endfor

                <!-- Cell Info Modal -->
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-show="showCellModal" x-cloak>
                    <div class="surface-read text-on-surface max-w-md w-full max-h-[50vh] overflow-hidden flex flex-col" @click.away="closeCellModal()">
                        <div class="flex gap-2items-center justify-between p-4 border-b border-surface/20">
                            <x-shared::title tag="h3" x-text="`{{ __('jardino::planting.position') }} ${selectedCellX}, ${selectedCellY}`"></x-shared::title>
                            <button @click="closeCellModal()" class="text-fg/60 hover:text-fg">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>

                        <div class="p-4 flex flex-col gap-2 flex-1 min-h-0">
                            <div x-show="canUnplant">
                                <p class="text-center mb-4">{{ __('jardino::planting.confirm_unplant') }}</p>
                                <div class="flex gap-2">
                                    <x-shared::button x-on:click="closeCellModal()" color="neutral" :outline="true">
                                        {{ __('jardino::planting.cancel') }}
                                    </x-shared::button>
                                    <x-shared::button x-on:click="unplantFlower()" color="accent">
                                        {{ __('jardino::planting.unplant') }}
                                    </x-shared::button>
                                </div>
                            </div>

                            <div x-show="!isOwnFlower && targetUserId">
                                <div class="flex items-center justify-center mx-auto gap-8">
                                    <x-shared::avatar x-bind:src="targetAvatarUrl"
                                        x-bind:alt="targetDisplayName ? targetDisplayName + ' avatar' : ''"
                                        class="w-16 h-16 rounded-full object-cover"
                                        x-show="targetAvatarUrl"></x-shared::avatar>
                                    <p class="text-lg font-medium" x-text="targetDisplayName || 'Utilisateur #' + targetUserId"></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-4 gap-2 p-2 flex-1 overflow-y-auto" x-show="showPlanting">
                                @for($i = 1; $i <= 28; $i++)
                                    @php
                                    $flowerNumber=str_pad($i, 2, '0' , STR_PAD_LEFT);
                                    $flowerPath='images/activities/jardino/' . $flowerNumber . '.png' ;
                                    @endphp
                                    <button @click="selectedFlower = '{{ $flowerNumber }}'"
                                    class="flower-option p-1 border-2 transition-all hover:border-accent hover:scale-105"
                                    :class="{ 'border-accent bg-accent/10': selectedFlower === '{{ $flowerNumber }}', 'border-surface/30': selectedFlower !== '{{ $flowerNumber }}' }">
                                    <img src="{{ asset($flowerPath) }}"
                                        alt="Flower {{ $flowerNumber }}"
                                        class="w-full h-12 object-contain"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    </button>
                                    @endfor
                            </div>

                            <div class="flex gap-2" x-show="showPlanting">
                                <x-shared::button x-on:click="closeCellModal()" color="neutral" :outline="true">
                                    {{ __('jardino::planting.cancel') }}
                                </x-shared::button>
                                <x-shared::button x-on:click="plantFlower()"
                                    x-bind:disabled="!selectedFlower"
                                    color="accent">
                                    {{ __('jardino::planting.plant') }}
                                </x-shared::button>
                            </div>

                            <div class="flex gap-4" x-show="!showPlanting && !canUnplant">
                                <x-shared::button x-on:click="showPlanting = true"
                                    x-bind:disabled="!canPlant"
                                    color="accent">
                                    {{ __('jardino::planting.plant') }}
                                </x-shared::button>

                                <div x-show="canBlock">
                                    <x-shared::button x-on:click="blockCell()" color="accent">
                                        {{ __('jardino::planting.block_cell') }}
                                    </x-shared::button>
                                </div>
                                <div x-show="canUnblock">
                                    <x-shared::button x-on:click="unblockCell()" color="accent">
                                        {{ __('jardino::planting.unblock') }}
                                    </x-shared::button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
    </div>

    <div class="mt-4 text-sm text-fg/70">
        <p>{{ __('jardino::garden.stats', [
                'occupied' => count($viewModel->gardenMap->occupiedCells),
                'total' => $viewModel->gardenMap->getTotalCells(),
                'empty' => $viewModel->gardenMap->getEmptyCells()
            ]) }}</p>
        @if($viewModel->objective && $viewModel->objective->flowersAvailable <= 0)
            <p class="mt-2 text-amber-600 dark:text-amber-400">
            <span class="material-symbols-outlined text-sm align-middle mr-1">info</span>
            {{ __('jardino::planting.no_flowers_available') }}
            </p>
            @endif
    </div>
    @else
    <div class="text-center py-8 text-fg/50">
        <span class="material-symbols-outlined text-4xl mb-2">grass</span>
        <p>{{ __('jardino::garden.no_map') }}</p>
    </div>
    @endif

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('flowerModal', ({
                cellX,
                cellY,
                activityId,
                flowersAvailable,
                currentUserId,
                isAdmin,
            }) => ({
                showCellModal: false,
                selectedFlower: null,
                selectedCellX: null,
                selectedCellY: null,
                canPlant: null,
                canUnplant: null,
                canBlock: null,
                canUnblock: null,
                activityId: activityId,
                flowersAvailable: flowersAvailable,
                currentUserId: currentUserId,
                isAdmin: isAdmin,
                targetUserId: null,
                targetDisplayName: null,
                targetAvatarUrl: null,
                isOwnFlower: false,
                showPlanting: false,

                handleCellClick(el) {
                    this.selectedCellX = parseInt(el.dataset.x, 10);
                    this.selectedCellY = parseInt(el.dataset.y, 10);
                    this.showPlanting = false;
                    const isOccupied = el.dataset.occupied === 'true';
                    const type = el.dataset.type || null;
                    const userId = el.dataset.userId ? parseInt(el.dataset.userId, 10) : null;
                    this.targetUserId = userId;
                    this.targetDisplayName = el.dataset.displayName || null;
                    this.targetAvatarUrl = el.dataset.avatarUrl || null;

                    this.canPlant = !isOccupied && this.flowersAvailable > 0;
                    this.canUnplant = isOccupied && userId == this.currentUserId && type === 'flower';
                    this.canBlock = !isOccupied && this.isAdmin;
                    this.canUnblock = type === 'blocked' && this.isAdmin;
                    this.isOwnFlower = userId == this.currentUserId;

                    this.openCellModal();
                },

                openCellModal() {
                    this.showCellModal = true;
                },

                closeCellModal() {
                    this.showCellModal = false;
                },

                plantFlower() {
                    if (!this.selectedFlower) return;

                    console.log('Planting flower:', this.selectedFlower, 'at', this.selectedCellX, this.selectedCellY);

                    fetch(`/calendar/activities/${this.activityId}/jardino/plant-flower`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                x: this.selectedCellX,
                                y: this.selectedCellY,
                                flower_image: this.selectedFlower + '.png'
                            })
                        })
                        .then(response => {
                            if (response.ok) {
                                this.closeCellModal();
                                location.reload();
                            } else {
                                console.error('Failed to plant flower');
                            }
                        })
                        .catch(error => {
                            console.error('Error planting flower:', error);
                        });
                },

                unplantFlower() {
                    console.log('Removing flower at', this.selectedCellX, this.selectedCellY);

                    fetch(`/calendar/activities/${this.activityId}/jardino/remove-flower`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                x: this.selectedCellX,
                                y: this.selectedCellY
                            })
                        })
                        .then(response => {
                            if (response.ok) {
                                this.closeCellModal();
                                location.reload();
                            } else {
                                console.error('Failed to remove flower');
                            }
                        })
                        .catch(error => {
                            console.error('Error removing flower:', error);
                        });
                },

                blockCell() {
                    console.log('Blocking cell at', this.selectedCellX, this.selectedCellY);

                    fetch(`/calendar/activities/${this.activityId}/jardino/block-cell`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                x: this.selectedCellX,
                                y: this.selectedCellY
                            })
                        })
                        .then(response => {
                            if (response.ok) {
                                this.closeCellModal();
                                location.reload();
                            } else {
                                console.error('Failed to block cell');
                            }
                        })
                        .catch(error => {
                            console.error('Error blocking cell:', error);
                        });
                },

                unblockCell() {
                    console.log('Unblocking cell at', this.selectedCellX, this.selectedCellY);

                    fetch(`/calendar/activities/${this.activityId}/jardino/unblock-cell`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                x: this.selectedCellX,
                                y: this.selectedCellY
                            })
                        })
                        .then(response => {
                            if (response.ok) {
                                this.closeCellModal();
                                location.reload();
                            } else {
                                console.error('Failed to unblock cell');
                            }
                        })
                        .catch(error => {
                            console.error('Error unblocking cell:', error);
                        });
                }
            }));
        });
    </script>
</div>