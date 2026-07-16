@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div class="md:col-span-2">
        <x-input-label for="name" value="اسم الفترة" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      :value="old('name', $period->name)" required autofocus placeholder="مثال: صيف 2026" />
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="starts_on" value="تاريخ البداية" />
        <x-text-input id="starts_on" name="starts_on" type="date" class="mt-1 block w-full"
                      :value="old('starts_on', optional($period->starts_on)->format('Y-m-d'))" required />
        <x-input-error :messages="$errors->get('starts_on')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="ends_on" value="تاريخ النهاية" />
        <x-text-input id="ends_on" name="ends_on" type="date" class="mt-1 block w-full"
                      :value="old('ends_on', optional($period->ends_on)->format('Y-m-d'))" required />
        <x-input-error :messages="$errors->get('ends_on')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="required_hours" value="الساعات المطلوبة" />
        <x-text-input id="required_hours" name="required_hours" type="number" min="1" max="2000"
                      class="mt-1 block w-full" :value="old('required_hours', $period->required_hours ?? 180)" required />
        <x-input-error :messages="$errors->get('required_hours')" class="mt-1" />
    </div>

    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2">
            <input type="hidden" name="is_open" value="0">
            <input type="checkbox" name="is_open" value="1"
                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                   @checked(old('is_open', $period->is_open ?? false))>
            <span class="text-sm text-gray-700">فترة مفتوحة للتسجيل</span>
        </label>
        <p class="mt-1 text-xs text-gray-400">عند فتح هذه الفترة سيتم إغلاق أي فترة مفتوحة أخرى تلقائياً.</p>
    </div>
</div>
