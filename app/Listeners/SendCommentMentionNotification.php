<?php

namespace App\Listeners;

use App\Mail\CommentMentionMail;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Kirschbaum\Commentions\Events\UserWasMentionedEvent;

class SendCommentMentionNotification // implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(UserWasMentionedEvent $event): void
    {

        $comment = $event->comment;
        $mentionedUser = $event->user;
        $commenter = $comment->author;

        // Get the commentable model (e.g., DataRequestResponse)
        $commentable = $comment->commentable;

        // Determine a human-readable title for the commentable
        $commentableTitle = $this->getCommentableTitle($commentable);
        $commentUrl = $this->getCommentUrl($commentable);

        // Send database notification
        $mentionedUser->notify(new \App\Notifications\DropdownNotification(
            title: 'You were mentioned in a comment',
            body: "{$commenter->name} mentioned you in a comment on {$commentableTitle}",
            icon: 'heroicon-o-chat-bubble-left-ellipsis',
            color: 'primary',
            actionUrl: $commentUrl,
            actionLabel: 'View Comment'
        ));

        // Send email notification
        Mail::to($mentionedUser->email)->send(
            new CommentMentionMail($comment, $commentableTitle, $commentUrl)
        );

    }

    /**
     * Get a human-readable title for the commentable model
     */
    protected function getCommentableTitle($commentable): string
    {
        if (method_exists($commentable, 'getCommentableTitle')) {
            return $commentable->getCommentableTitle();
        }

        // Try common attribute names
        if (isset($commentable->title)) {
            return $commentable->title;
        }

        if (isset($commentable->name)) {
            return $commentable->name;
        }

        if (isset($commentable->code)) {
            return $commentable->code;
        }

        // Fallback to model type
        return class_basename($commentable);
    }

    /**
     * Generate URL to view the comment
     */
    protected function getCommentUrl($commentable): ?string
    {
        // For DataRequestResponse, generate the view URL (not edit, since followers may not have edit access)
        if ($commentable instanceof \App\Models\DataRequestResponse) {
            return route('filament.app.resources.data-request-responses.view', ['record' => $commentable->id]);
        }

        // Add more model types as needed

        return null;
    }
}
