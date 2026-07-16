@props(['action', 'label' => 'حذف', 'confirm' => 'هل أنت متأكد من الحذف؟'])

<form method="POST" action="{{ $action }}" class="inline"
      onsubmit="return confirm('{{ $confirm }}');">
    @csrf
    @method('DELETE')
    <button type="submit" class="text-red-600 hover:underline ms-3">{{ $label }}</button>
</form>
