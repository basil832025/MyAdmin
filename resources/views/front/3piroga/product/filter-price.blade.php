@php
    // Глобальные границы из контроллера
    $catalogMin = isset($priceMin) ? (int) $priceMin : 0;
    $catalogMax = isset($priceMax) ? (int) $priceMax : 0;

    // Текущее значение из запроса, если уже фильтровали
    $reqMin = request()->filled('price_min') ? (int) request('price_min') : $catalogMin;
    $reqMax = request()->filled('price_max') ? (int) request('price_max') : $catalogMax;

    // Подстраховка: не выходим за границы и не даём min > max
    $fromInit = max($catalogMin, min($reqMin, $catalogMax));
    $toInit   = max($catalogMin, min($reqMax, $catalogMax));
    if ($fromInit > $toInit) {
        $fromInit = $toInit;
    }
@endphp

<div class="w-full lg:w-[220px] flex-shrink-0"
     x-data="{
        min: {{ $catalogMin }},
        max: {{ $catalogMax }},
        from: {{ $fromInit }},
        to:   {{ $toInit }},
        dragging: null,
        trackRect: null,

        get fromLabel() { return this.from + ' {{ st('all.grn','грн') }}' },
        get toLabel()   { return this.to   + ' {{ st('all.grn','грн') }}' },

        get fromPct() {
            return this.max === this.min ? 0 : ((this.from - this.min) / (this.max - this.min)) * 100;
        },
        get toPct() {
            return this.max === this.min ? 100 : ((this.to - this.min) / (this.max - this.min)) * 100;
        },

        clampValues() {
            if (this.from < this.min) this.from = this.min;
            if (this.to   > this.max) this.to   = this.max;
            if (this.from > this.to)  this.from = this.to;
        },

        updateTrackRect() {
            this.$nextTick(() => {
                if (this.$refs.track) {
                    this.trackRect = this.$refs.track.getBoundingClientRect();
                }
            });
        },

        valueFromClientX(event) {
            if (!this.trackRect) this.updateTrackRect();
            if (!this.trackRect) return this.min;

            const x = event.clientX - this.trackRect.left;
            let pct = x / this.trackRect.width;
            if (pct < 0) pct = 0;
            if (pct > 1) pct = 1;

            const val = this.min + pct * (this.max - this.min);
            // шаг 10 грн
            return Math.round(val / 10) * 10;
        },

        startDrag(which, event) {
            if (this.max === this.min) return;
            this.dragging = which;
            this.updateTrackRect();
            this.onDrag(event);
        },

        onDrag(event) {
            if (!this.dragging || this.max === this.min) return;

            const val = this.valueFromClientX(event);

            if (this.dragging === 'from') {
                this.from = val;
                if (this.from > this.to) this.from = this.to;
                if (this.from < this.min) this.from = this.min;
            } else if (this.dragging === 'to') {
                this.to = val;
                if (this.to < this.from) this.to = this.from;
                if (this.to > this.max) this.to = this.max;
            }
        },

        stopDrag() {
            this.dragging = null;
        },

        onTrackClick(event) {
            if (this.max === this.min) return;
            this.updateTrackRect();
            const val = this.valueFromClientX(event);
            const distFrom = Math.abs(val - this.from);
            const distTo   = Math.abs(val - this.to);

            if (distFrom <= distTo) {
                this.from = Math.min(val, this.to);
            } else {
                this.to = Math.max(val, this.from);
            }
        }
     }"
     x-init="updateTrackRect()"
     @resize.window="updateTrackRect()"
     @pointermove.window="onDrag($event)"
     @pointerup.window="stopDrag()"
     @pointercancel.window="stopDrag()"
>

    {{-- ВАЖНО: именно эти поля улетают в запрос --}}
    <input type="hidden" name="price_min" :value="from">
    <input type="hidden" name="price_max" :value="to">

    <div class="flex flex-col gap-4">

        {{-- поля От / До --}}
        <div class="flex items-end gap-2">
            {{-- От --}}
            <div class="flex-1 min-w-0">
                <label class="block text-xs text-[#6B7280] mb-1"> {{ st('all.from','Від') }}</label>
                <div class="h-[40px] border border-[#E5E7EB] rounded-[8px] px-3 flex items-center">
                    <input type="number"
                           x-model.number="from"
                           @input="clampValues()"
                           class="w-full text-sm outline-none">
                    <span class="ml-1 text-xs text-[#6B7280]">{{ st('all.grn','грн') }}</span>
                </div>
            </div>

            <div class="hidden md:block text-[#9CA3AF] pb-2 px-1">—</div>

            {{-- До --}}
            <div class="flex-1 min-w-0">
                <label class="block text-xs text-[#6B7280] mb-1">{{ st('all.to','До') }}</label>
                <div class="h-[40px] border border-[#E5E7EB] rounded-[8px] px-3 flex items-center">
                    <input type="number"
                           x-model.number="to"
                           @input="clampValues()"
                           class="w-full text-sm outline-none text-right">
                    <span class="ml-1 text-xs text-[#6B7280]">{{ st('all.grn','грн') }}</span>
                </div>
            </div>
        </div>

        {{-- ползунок --}}
        <div class="mt-2">
            <div
                class="relative h-1 bg-[#E5E7EB] rounded-full mx-1 select-none"
                x-ref="track"
                @pointerdown="onTrackClick($event)"
            >
                {{-- активная часть --}}
                <div class="absolute top-1/2 -translate-y-1/2 h-1 bg-[#FF7500] rounded-full"
                     :style="`left:${fromPct}%; right:${100 - toPct}%`">
                </div>

                {{-- левая ручка --}}
                <div class="absolute top-1/2 -translate-y-1/2 w-4 h-4 rounded-full border border-[#FF7500] bg-white"
                     :style="`left: calc(${fromPct}% - 8px)`"
                     @pointerdown.stop="startDrag('from', $event)">
                </div>

                {{-- правая ручка --}}
                <div class="absolute top-1/2 -translate-y-1/2 w-4 h-4 rounded-full border border-[#FF7500] bg-white"
                     :style="`left: calc(${toPct}% - 8px)`"
                     @pointerdown.stop="startDrag('to', $event)">
                </div>
            </div>

            <div class="mt-2 flex justify-between text-xs text-[#FF7500] mx-1">
                <span x-text="fromLabel"></span>
                <span x-text="toLabel"></span>
            </div>
        </div>

    </div>
</div>
