@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div class="md:col-span-2">
        <x-input-label for="name" value="اسم المؤسسة" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      :value="old('name', $organization->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="sector" value="القطاع" />
        <x-text-input id="sector" name="sector" type="text" class="mt-1 block w-full"
                      :value="old('sector', $organization->sector)" />
        <x-input-error :messages="$errors->get('sector')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="address" value="العنوان" />
        <x-text-input id="address" name="address" type="text" class="mt-1 block w-full"
                      :value="old('address', $organization->address)" />
        <x-input-error :messages="$errors->get('address')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="contact_name" value="اسم جهة الاتصال" />
        <x-text-input id="contact_name" name="contact_name" type="text" class="mt-1 block w-full"
                      :value="old('contact_name', $organization->contact_name)" />
        <x-input-error :messages="$errors->get('contact_name')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="contact_phone" value="هاتف جهة الاتصال" />
        <x-text-input id="contact_phone" name="contact_phone" type="text" class="mt-1 block w-full"
                      :value="old('contact_phone', $organization->contact_phone)" />
        <x-input-error :messages="$errors->get('contact_phone')" class="mt-1" />
    </div>

    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1"
                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                   @checked(old('is_active', $organization->is_active ?? true))>
            <span class="text-sm text-gray-700">مؤسسة مُفعّلة</span>
        </label>
    </div>
</div>
