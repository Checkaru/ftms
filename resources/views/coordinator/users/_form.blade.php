@csrf

@php
    $selectClass = 'mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm';
@endphp

<div x-data="{ role: '{{ old('role', $user->role?->value ?? '') }}' }"
     class="grid grid-cols-1 md:grid-cols-2 gap-5">

    <div>
        <x-input-label for="name" value="الاسم" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      :value="old('name', $user->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="email" value="البريد الإلكتروني" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                      :value="old('email', $user->email)" required />
        <x-input-error :messages="$errors->get('email')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="role" value="الدور" />
        <select id="role" name="role" class="{{ $selectClass }}" x-model="role" required>
            <option value="">— اختر الدور —</option>
            @foreach (\App\Enums\UserRole::cases() as $case)
                <option value="{{ $case->value }}"
                    @selected(old('role', $user->role?->value) === $case->value)>
                    {{ $case->label() }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('role')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="phone" value="الهاتف" />
        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                      :value="old('phone', $user->phone)" />
        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
    </div>

    <div x-show="role === 'student'" x-cloak>
        <x-input-label for="student_number" value="الرقم الجامعي" />
        <x-text-input id="student_number" name="student_number" type="text" class="mt-1 block w-full"
                      :value="old('student_number', $user->student_number)" />
        <x-input-error :messages="$errors->get('student_number')" class="mt-1" />
    </div>

    <div x-show="role === 'field_supervisor'" x-cloak>
        <x-input-label for="organization_id" value="المؤسسة" />
        <select id="organization_id" name="organization_id" class="{{ $selectClass }}">
            <option value="">— اختر المؤسسة —</option>
            @foreach ($organizations as $organization)
                <option value="{{ $organization->id }}"
                    @selected((int) old('organization_id', $user->organization_id) === $organization->id)>
                    {{ $organization->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('organization_id')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="password" value="كلمة المرور" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                      autocomplete="new-password" />
        @if ($user->exists)
            <p class="mt-1 text-xs text-gray-400">اتركها فارغة للإبقاء على كلمة المرور الحالية.</p>
        @endif
        <x-input-error :messages="$errors->get('password')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="password_confirmation" value="تأكيد كلمة المرور" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                      class="mt-1 block w-full" autocomplete="new-password" />
    </div>

    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1"
                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                   @checked(old('is_active', $user->is_active ?? true))>
            <span class="text-sm text-gray-700">حساب مُفعّل</span>
        </label>
    </div>
</div>
