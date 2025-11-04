<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class ViewContact extends ViewRecord
{
    protected static string $resource = ContactResource::class;

    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('quick_reply')
                ->label('快速回覆')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('reply_message')
                        ->label('回覆訊息')
                        ->required()
                        ->rows(6)
                        ->helperText('快速回覆給用戶，可選擇是否發送通知'),

                    Forms\Components\Checkbox::make('send_notification')
                        ->label('發送通知給用戶')
                        ->default(true)
                        ->helperText('勾選後系統會發送郵件通知'),
                ])
                ->action(function (array $data) {
                    $record = $this->getRecord();

                    try {
                        // 更新聯絡表單狀態為處理中
                        $record->markAsProcessing();

                        // 更新回覆內容
                        $record->update(['reply_message' => $data['reply_message']]);

                        // 如果選擇發送通知
                        if ($data['send_notification']) {
                            try {
                                // 發送回覆郵件
                                Mail::to($record->email)->send(
                                    new \App\Mail\ContactReplyNotification($record, $data['reply_message'])
                                );

                                $record->recordNotificationSent();

                            } catch (\Exception $e) {
                                \Log::error('Failed to send contact reply email: ' . $e->getMessage());
                            }
                        }

                        // 如果有電話號且未發送過通知，發送電話通知
                        if ($record->shouldSendPhoneNotification()) {
                            $notificationMessage = "您的聯絡「{$record->subject}」已收到回覆，詳情請查看郵件或聯繫我們的客服。";
                            $record->sendPhoneNotification($notificationMessage);
                            $record->recordNotificationSent();
                        }

                        // 標記為已回覆
                        $record->markAsReplied($data['reply_message'], Auth::id());

                        $this->notify('success', '回覆已發送成功！');
                    } catch (\Exception $e) {
                        \Log::error('Failed to send contact reply: ' . $e->getMessage());
                        $this->notify('danger', '處理回覆失敗：' . $e->getMessage());
                    }
                })
                ->visible(fn (): bool => $this->getRecord()->isPending() || $this->getRecord()->status === \App\Models\Contact::STATUS_PROCESSING),

            Actions\Action::make('send_notification')
                ->label('發送通知')
                ->icon('heroicon-o-bell')
                ->color('primary')
                ->requiresConfirmation('確認要發送通知給用戶嗎？')
                ->action(function () {
                    $record = $this->getRecord();

                    try {
                        // 發送通知郵件
                        $notificationMessage = "您的聯絡表單「{$record->subject}」我們已收到，將會盡快處理並回覆您。";
                        Mail::to($record->email)->send(
                            new \App\Mail\ContactSubmissionNotification($record)
                        );

                        // 記錄已發送通知
                        $record->recordNotificationSent();

                        // 如果有電話號，發送電話通知
                        if ($record->shouldSendPhoneNotification()) {
                            $record->sendPhoneNotification($notificationMessage);
                            $record->recordNotificationSent();
                        }

                        $this->notify('success', '通知已發送成功！');
                    } catch (\Exception $e) {
                        \Log::error('Failed to send notification: ' . $e->getMessage());
                        $this->notify('danger', '發送通知失敗：' . $e->getMessage());
                    }
                })
                ->visible(fn (): bool => !$this->getRecord()->send_notification),

            Actions\Action::make('mark_as_processing')
                ->label('標記為處理中')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(fn () => $this->getRecord()->markAsProcessing())
                ->visible(fn (): bool => $this->getRecord()->isPending()),

            Actions\Action::make('mark_as_closed')
                ->label('標記為已解決')
                ->icon('heroicon-o-check-circle')
                ->color('gray')
                ->requiresConfirmation()
                ->action(fn () => $this->getRecord()->markAsClosed())
                ->visible(fn (): bool => !$this->getRecord()->isReplied()),
        ];
    }

    public function getTitle(): string
    {
        return '查看聯絡表單';
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/admin/contacts' => '聯絡表單管理',
            '#' => $this->getRecord()->subject,
        ];
    }
}