@props([
    'survey' => null,
])

@php
    use App\Enums\QuestionType;
@endphp

@if($survey && $survey->template)
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
    {{-- Header --}}
    <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ $survey->display_title }}
        </h2>
        @if($survey->template->description)
            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400 prose dark:prose-invert prose-sm max-w-none">
                {!! $survey->template->description !!}
            </div>
        @endif
    </div>

    {{-- Checklist Items --}}
    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @foreach($survey->template->questions->sortBy('sort_order') as $index => $question)
            @php
                $answer = $survey->answers->where('survey_question_id', $question->id)->first();
                $answerValue = $answer?->answer_value;
                $comment = $answer?->comment;
                $attachments = $answer?->attachments ?? collect();
            @endphp

            <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                <div class="flex items-start gap-4">
                    {{-- Item Number --}}
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                        <span class="text-sm font-medium text-primary-700 dark:text-primary-300">{{ $index + 1 }}</span>
                    </div>

                    <div class="flex-1 min-w-0">
                        {{-- Question --}}
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $question->question_text }}
                                    @if($question->is_required)
                                        <span class="text-danger-500">*</span>
                                    @endif
                                </p>
                                @if($question->help_text)
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $question->help_text }}</p>
                                @endif
                            </div>
                        </div>

                        {{-- Answer --}}
                        <div class="mt-2">
                            @if($question->question_type === QuestionType::BOOLEAN)
                                @if($answerValue === 'yes')
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-success-50 dark:bg-success-900/30 text-success-700 dark:text-success-300 text-sm font-medium">
                                        <x-heroicon-m-check-circle class="w-4 h-4" />
                                        {{ __('Yes') }}
                                    </div>
                                @elseif($answerValue === 'no')
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-danger-50 dark:bg-danger-900/30 text-danger-700 dark:text-danger-300 text-sm font-medium">
                                        <x-heroicon-m-x-circle class="w-4 h-4" />
                                        {{ __('No') }}
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400 dark:text-gray-500 italic">{{ __('Not answered') }}</span>
                                @endif

                            @elseif($question->question_type === QuestionType::SINGLE_CHOICE)
                                @if($answerValue)
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm">
                                        <x-heroicon-m-check class="w-4 h-4 text-primary-500" />
                                        {{ $answer->display_value }}
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400 dark:text-gray-500 italic">{{ __('Not answered') }}</span>
                                @endif

                            @elseif($question->question_type === QuestionType::MULTIPLE_CHOICE)
                                @if($answerValue && is_array($answerValue) && count($answerValue) > 0)
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($answerValue as $value)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm">
                                                <x-heroicon-m-check class="w-3.5 h-3.5 text-primary-500" />
                                                {{ $value }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400 dark:text-gray-500 italic">{{ __('Not answered') }}</span>
                                @endif

                            @elseif($question->question_type === QuestionType::FILE)
                                @if($attachments->count() > 0)
                                    <div class="flex flex-col gap-1.5">
                                        @foreach($attachments as $attachment)
                                            <div class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-800 text-sm">
                                                <x-heroicon-m-paper-clip class="w-4 h-4 text-gray-500" />
                                                <span class="text-gray-700 dark:text-gray-300">{{ $attachment->file_name }}</span>
                                                <span class="text-gray-400 text-xs">({{ number_format($attachment->file_size / 1024, 1) }} KB)</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400 dark:text-gray-500 italic">{{ __('No files uploaded') }}</span>
                                @endif

                            @elseif($question->question_type === QuestionType::LONG_TEXT)
                                @if($answerValue)
                                    <div class="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 rounded-md p-3 whitespace-pre-wrap">{{ $answer->display_value }}</div>
                                @else
                                    <span class="text-sm text-gray-400 dark:text-gray-500 italic">{{ __('Not answered') }}</span>
                                @endif

                            @else
                                @if($answerValue)
                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $answer->display_value }}</p>
                                @else
                                    <span class="text-sm text-gray-400 dark:text-gray-500 italic">{{ __('Not answered') }}</span>
                                @endif
                            @endif
                        </div>

                        {{-- Comment/Notes --}}
                        @if($comment)
                            <div class="mt-2 pl-3 border-l-2 border-gray-200 dark:border-gray-700">
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ __('Notes') }}:</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $comment }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Footer with Completion/Signature Info --}}
    @if($survey->completed_at || $survey->isApproved())
        <div class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                {{-- Completion Info --}}
                @if($survey->completed_at)
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <x-heroicon-m-check-circle class="w-5 h-5 text-success-500" />
                        <span>
                            {{ __('Completed') }}
                            @if($survey->assignedTo)
                                {{ __('by') }} <span class="font-medium text-gray-900 dark:text-white">{{ $survey->assignedTo->name }}</span>
                            @endif
                            {{ __('on') }} {{ $survey->completed_at->format('M j, Y \a\t g:i A') }}
                        </span>
                    </div>
                @endif
            </div>

            {{-- Signature Block --}}
            @if($survey->isApproved())
                @php
                    $approval = $survey->latestApproval;
                @endphp
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <x-signature-block
                        :signature="$approval->signature"
                        :signerName="$approval->approver_name"
                        :signedAt="$approval->approved_at"
                    />

                    @if($approval->notes)
                        <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-medium">{{ __('Approval Notes') }}:</span>
                            {{ $approval->notes }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>
@endif
