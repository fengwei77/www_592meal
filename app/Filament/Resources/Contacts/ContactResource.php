<?php

namespace App\Filament\Resources\Contacts;

use App\Filament\Resources\Contacts\Pages\CreateContact;
use App\Filament\Resources\Contacts\Pages\EditContact;
use App\Filament\Resources\Contacts\Pages\ListContacts;
use App\Filament\Resources\Contacts\Pages\ViewContact;
use App\Models\Contact;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $modelLabel = '聯絡表單';

    protected static ?string $pluralModelLabel = '聯絡表單';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-envelope';

    /**
     * 只有系統管理員可以存取
     */
    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    /**
     * 查詢範圍 - 只有 super_admin 可以看到所有
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // 只有 Super Admin 可以查看所有聯絡表單
        if (!Auth::user()?->hasRole('super_admin')) {
            return $query->whereRaw('1 = 0'); // 返回空查詢
        }

        return $query->latest();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('聯絡資訊')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('姓名')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('電話')
                            ->tel()
                            ->maxLength(20)
                            ->helperText('用於事後電話通知'),

                        Forms\Components\TextInput::make('subject')
                            ->label('主題')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('message')
                            ->label('訊息內容')
                            ->required()
                            ->rows(4),
                    ])
                    ->columns(2),

                Section::make('處理狀態')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('狀態')
                            ->options(Contact::getStatusOptions())
                            ->required()
                            ->reactive(),

                        Forms\Components\Textarea::make('reply_message')
                            ->label('回覆訊息')
                            ->rows(4)
                            ->visible(fn (callable $get) => $get('status') === Contact::STATUS_REPLIED),

                        Forms\Components\Textarea::make('admin_notes')
                            ->label('管理員備註')
                            ->rows(3),

                        Section::make('通知設定')
                            ->description('選擇是否發送事後通知給用戶')
                            ->schema([
                                Forms\Components\Checkbox::make('send_notification')
                                    ->label('發送通知給用戶')
                                    ->default(true)
                                    ->helperText('勾選後系統會發送郵件或簡訊通知給用戶'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('name')
                    ->label('姓名')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('電話')
                    ->searchable()
                    ->sortable()
                    ->placeholder('未提供'),

                Tables\Columns\TextColumn::make('subject')
                    ->label('主題')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->tooltip(fn (Contact $record): string => $record->subject),

                Tables\Columns\TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Contact::STATUS_PENDING => 'warning',
                        Contact::STATUS_PROCESSING => 'info',
                        Contact::STATUS_REPLIED => 'success',
                        Contact::STATUS_CLOSED => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('send_notification')
                    ->label('已通知')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('提交時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->description('最近聯絡'),

                Tables\Columns\TextColumn::make('replied_at')
                    ->label('回覆時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->placeholder('尚未回覆')
                    ->description('最後處理時間'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('狀態')
                    ->options(Contact::getStatusOptions()),

                Tables\Filters\Filter::make('created_at')
                    ->label('提交時間')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('開始日期'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('結束日期'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('notification_sent')
                    ->label('通知狀態')
                    ->options([
                        '1' => '已發送',
                        '0' => '未發送',
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
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
                    ->action(function (Contact $record, array $data) {
                        try {
                            // 更新聯絡表單狀態為處理中
                            $record->markAsProcessing();

                            // 更新回覆內容
                            $record->update(['reply_message' => $data['reply_message']]);

                            // 如果選擇發送通知
                            if ($data['send_notification']) {
                                try {
                                    // 確保 email 是有效字串
                                    $email = is_string($record->email) ? $record->email : (string) $record->email;

                                    // 發送回覆郵件
                                    Mail::to($email)->send(
                                        new \App\Mail\ContactReplyNotification($record, $data['reply_message'])
                                    );

                                    $record->recordNotificationSent();

                                    \Log::info('Contact reply email sent successfully', [
                                        'contact_id' => $record->id,
                                        'email' => $email
                                    ]);

                                } catch (\Exception $e) {
                                    \Log::error('Failed to send contact reply email: ' . $e->getMessage());
                                    \Log::error('Contact details', [
                                        'contact_id' => $record->id,
                                        'email_type' => gettype($record->email),
                                        'email_value' => $record->email
                                    ]);
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

                            return redirect()->back();
                        } catch (\Exception $e) {
                            \Log::error('Failed to send contact reply: ' . $e->getMessage());
                            throw new \Exception('處理回覆失敗：' . $e->getMessage());
                        }
                    })
                    ->visible(fn (Contact $record): bool => $record->isPending() || $record->status === Contact::STATUS_PROCESSING),

                Actions\Action::make('send_notification')
                    ->label('發送通知')
                    ->icon('heroicon-o-bell')
                    ->color('primary')
                    ->requiresConfirmation('確認要發送通知給用戶嗎？')
                    ->action(function (Contact $record) {
                        try {
                            // 確保 email 是有效字串
                            $email = is_string($record->email) ? $record->email : (string) $record->email;

                            // 發送通知郵件
                            $notificationMessage = "您的聯絡表單「{$record->subject}」我們已收到，將會盡快處理並回覆您。";
                            Mail::to($email)->send(
                                new \App\Mail\ContactSubmissionNotification($record)
                            );

                            // 記錄已發送通知
                            $record->recordNotificationSent();

                            \Log::info('Contact notification email sent successfully', [
                                'contact_id' => $record->id,
                                'email' => $email
                            ]);

                            // 如果有電話號，發送電話通知
                            if ($record->shouldSendPhoneNotification()) {
                                $record->sendPhoneNotification($notificationMessage);
                                $record->recordNotificationSent();
                            }

                            return redirect()->back();
                        } catch (\Exception $e) {
                            \Log::error('Failed to send notification: ' . $e->getMessage());
                            throw new \Exception('發送通知失敗：' . $e->getMessage());
                        }
                    })
                    ->visible(fn (Contact $record): bool => !$record->send_notification),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('mark_as_processing')
                        ->label('標記為處理中')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->action(function (Builder $query) {
                            $query->update([
                                'status' => Contact::STATUS_PROCESSING,
                            ]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    Actions\BulkAction::make('mark_as_replied')
                        ->label('標記為已回覆')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function (Builder $query) {
                            $query->update([
                                'status' => Contact::STATUS_REPLIED,
                                'replied_at' => now(),
                                'replied_by' => Auth::id(),
                            ]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    Actions\BulkAction::make('mark_as_closed')
                        ->label('標記為已解決')
                        ->icon('heroicon-o-check-circle')
                        ->color('gray')
                        ->action(function (Builder $query) {
                            $query->update(['status' => Contact::STATUS_CLOSED]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    Actions\BulkAction::make('send_notifications')
                        ->label('批量發送通知')
                        ->icon('heroicon-o-bell')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->action(function (Builder $query) {
                            $contacts = $query->get();

                            foreach ($contacts as $contact) {
                                if ($contact->shouldSendPhoneNotification()) {
                                    try {
                                        $notificationMessage = "您的聯絡「{$contact->subject}」我們已收到，將會盡快處理並回覆您。";
                                        $contact->sendPhoneNotification($notificationMessage);
                                        $contact->recordNotificationSent();
                                    } catch (\Exception $e) {
                                        \Log::error("Failed to send phone notification for contact {$contact->id}: " . $e->getMessage());
                                    }
                                }

                                if (!$contact->send_notification) {
                                    try {
                                        // 確保 email 是有效字串
                                        $email = is_string($contact->email) ? $contact->email : (string) $contact->email;

                                        Mail::to($email)->send(
                                            new \App\Mail\ContactSubmissionNotification($contact)
                                        );
                                        $contact->recordNotificationSent();

                                        \Log::info('Batch contact notification email sent successfully', [
                                            'contact_id' => $contact->id,
                                            'email' => $email
                                        ]);
                                    } catch (\Exception $e) {
                                        \Log::error("Failed to send email notification for contact {$contact->id}: " . $e->getMessage());
                                        \Log::error('Batch contact details', [
                                            'contact_id' => $contact->id,
                                            'email_type' => gettype($contact->email),
                                            'email_value' => $contact->email
                                        ]);
                                    }
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContacts::route('/'),
            'create' => CreateContact::route('/create'),
            'view' => ViewContact::route('/{record}'),
            'edit' => EditContact::route('/{record}/edit'),
        ];
    }
}