<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            سجلات الحضور المعلّقة
            @if ($logs->isNotEmpty())
                <span class="text-sm font-normal text-gray-400">({{ $logs->count() }})</span>
            @endif
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            @if ($logs->isEmpty())
                <div class="rounded-lg bg-white shadow-sm p-8 text-center text-gray-500">
                    لا توجد سجلات بانتظار المراجعة. كل شيء محدَّث. ✅
                </div>
            @else
                <div x-data="{
                        selected: [],
                        allIds: @js($logs->pluck('id')),
                        rejectOpen: false, rejectAction: '', rejectStudent: '', rejectDate: '',
                        toggleAll(e) { this.selected = e.target.checked ? [...this.allIds] : [] },
                        selectWeek(ids) { this.selected = [...new Set([...this.selected, ...ids])] },
                        openReject(action, student, date) {
                            this.rejectAction = action; this.rejectStudent = student;
                            this.rejectDate = date; this.rejectOpen = true;
                        }
                     }">

                    <!-- Bulk toolbar -->
                    <form method="POST" action="{{ route('field.logs.bulkApprove') }}" class="mb-4 flex items-center gap-3">
                        @csrf
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="ids[]" :value="id">
                        </template>
                        <button type="submit" :disabled="selected.length === 0"
                                class="px-4 py-2 bg-green-600 text-white text-sm rounded-md disabled:opacity-40 hover:bg-green-700">
                            اعتماد المحدد (<span x-text="selected.length"></span>)
                        </button>
                        <label class="text-sm text-gray-500 inline-flex items-center gap-2">
                            <input type="checkbox" @change="toggleAll($event)"
                                   class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            تحديد الكل
                        </label>
                    </form>

                    @foreach ($logs->groupBy(fn ($log) => $log->work_date->copy()->startOfWeek()->format('Y-m-d')) as $weekStart => $weekLogs)
                        @php $weekIds = $weekLogs->pluck('id')->all(); @endphp
                        <div class="mb-6 bg-white shadow-sm rounded-lg overflow-hidden">
                            <div class="px-4 py-2 bg-gray-50 border-b flex items-center justify-between">
                                <span class="text-sm text-gray-600">
                                    أسبوع {{ \Illuminate\Support\Carbon::parse($weekStart)->format('Y/m/d') }}
                                    <span class="text-gray-400">({{ $weekLogs->count() }} سجل)</span>
                                </span>
                                <button type="button" @click="selectWeek(@js($weekIds))"
                                        class="text-xs text-green-700 hover:underline">تحديد الأسبوع</button>
                            </div>

                            <table class="min-w-full text-sm text-start">
                                <thead class="text-gray-500">
                                    <tr>
                                        <th class="px-3 py-2 w-8"></th>
                                        <th class="px-3 py-2 font-medium">الطالب</th>
                                        <th class="px-3 py-2 font-medium">التاريخ</th>
                                        <th class="px-3 py-2 font-medium">الوقت</th>
                                        <th class="px-3 py-2 font-medium">الساعات</th>
                                        <th class="px-3 py-2 font-medium">المهام</th>
                                        <th class="px-3 py-2 font-medium"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($weekLogs as $log)
                                        <tr>
                                            <td class="px-3 py-2">
                                                <input type="checkbox" :value="{{ $log->id }}" x-model.number="selected"
                                                       class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                            </td>
                                            <td class="px-3 py-2 font-medium text-gray-800">
                                                {{ $log->placement->student->name }}
                                            </td>
                                            <td class="px-3 py-2 text-gray-600 whitespace-nowrap">{{ $log->work_date->format('Y/m/d') }}</td>
                                            <td class="px-3 py-2 text-gray-500 whitespace-nowrap">{{ substr($log->check_in, 0, 5) }} – {{ substr($log->check_out, 0, 5) }}</td>
                                            <td class="px-3 py-2 text-gray-500">{{ $log->hours }}</td>
                                            <td class="px-3 py-2 text-gray-600 max-w-xs truncate" title="{{ $log->tasks }}">{{ $log->tasks }}</td>
                                            <td class="px-3 py-2 text-end whitespace-nowrap">
                                                <button type="button"
                                                        @click="$refs.approveForm.action='{{ route('field.logs.approve', $log) }}'; $refs.approveForm.submit()"
                                                        class="text-green-700 hover:underline">اعتماد</button>
                                                <button type="button"
                                                        @click="openReject('{{ route('field.logs.reject', $log) }}', @js($log->placement->student->name), '{{ $log->work_date->format('Y/m/d') }}')"
                                                        class="text-red-600 hover:underline ms-3">رفض</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach

                    <!-- Sibling one-click approve form (kept out of the bulk form to avoid nesting) -->
                    <form method="POST" x-ref="approveForm" class="hidden">@csrf</form>

                    <!-- Reject modal -->
                    <div x-show="rejectOpen" x-cloak style="display:none"
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                        <div @click.outside="rejectOpen=false" class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
                            <h3 class="font-semibold text-gray-800">رفض السجل</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                <span x-text="rejectStudent"></span> — <span x-text="rejectDate"></span>
                            </p>
                            <form method="POST" :action="rejectAction" class="mt-4">
                                @csrf
                                <label class="block text-sm text-gray-700 mb-1">سبب الرفض</label>
                                <textarea name="reason" rows="3" required maxlength="300"
                                          class="block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm"></textarea>
                                <div class="mt-4 flex gap-3">
                                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700">تأكيد الرفض</button>
                                    <button type="button" @click="rejectOpen=false" class="px-4 py-2 text-sm text-gray-600">إلغاء</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
