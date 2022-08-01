<?php

namespace App\Http\Controllers\Services;

use Carbon\Carbon;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class MessageService {

  public function getChatMessages(array $chatIDs) {
    $chatMessages = Message::select('id', 'reciever_id', 'sender_id', 'message', 'created_at', 'status')
                      ->whereIn('reciever_id', $chatIDs)
                      ->whereIn('sender_id', $chatIDs);
    return $chatMessages;
  }

  public function filterMessages(Builder $chatMessages) {
    // ->where('created_at', '>=', Carbon::now()->subdays(15))
    $filteredMessages = $chatMessages
                           ->orderBy('created_at', 'desc')
                           ->simplePaginate(20);
    return $filteredMessages;
  }

  public function readUnreadMessages(Builder $chatMessages, int $chatID) : bool {
    $allReadFlag = false;
    $unreadMessages = $chatMessages->where('sender_id', '<>', $chatID)->where('status', 0);
    $unreadCollection = $unreadMessages->get();
    if($unreadCollection->isNotEmpty()) {
      $unreadMessages->each(function ($item, $key) {
        $item->status = 1;
        $item->save();
      });
      $allReadFlag = true;
    }
    return $allReadFlag;
  }

  public function getChatsList(int $reciever_id) {
     $messages = Message::where(function ($query) use ($reciever_id) {
                            $query->where('reciever_id', '=', $reciever_id)
                                  ->orWhere('sender_id', '=', $reciever_id);
                        })
                        ->select('sender_id', DB::raw('MIN(status) as status'), DB::raw('MAX(created_at) as last_message_date'))
                        ->selectRaw("CASE WHEN sender_id = ".$reciever_id." THEN reciever_id ELSE sender_id END AS user_id")
                        ->groupBy('sender_id')
                        ->groupBy('reciever_id')
                        ->orderBy('status');
     return $messages;
   }

   public function getChatsInterlocutors(Builder $messages, int $reciever_id, ?string $status, string $orderByDate) {
     $users = User::joinSub($messages, 'messages', function ($join) {
                   $join->on('users.id', '=', 'messages.sender_id');
               })
               ->select('users.id', 'users.name', 'users.photo', 'messages.sender_id')
               ->selectRaw("CASE WHEN messages.sender_id = ".$reciever_id." THEN 1 ELSE MIN(messages.status) END AS status")
               ->selectRaw("MAX(messages.last_message_date) as last_message_date")
               ->where('users.id', '<>', $reciever_id)
               ->groupBy('messages.user_id')
               ->when(is_numeric($status), function ($query) use ($status) {
                         return $query->where('messages.status', $status);
                     })
               ->orderBy('messages.last_message_date', $orderByDate)
               ->paginate(Config::get('pagination.mainPage'));
     return $users;
   }

  public function sendMessage(User $user, Request $request) {
    $message = new Message;
    $message->message = $request->input('message');
    $message->reciever_id = $request->input('reciever_id');
    $message->status = $request->input('isMessageRead');
    $user->message()->save($message);
    return $message;
  }

  public function deleteOldMessages() : void {
    $deletedMessages = Message::where('created_at', '>=', Carbon::now()->subdays(180))->delete();
    return;
  }

}


//
//
// public function getChatsList(int $reciever_id) {
//    $messages = Message::where(function ($query) use ($reciever_id) {
//                           $query->where('reciever_id', '=', $reciever_id)
//                                 ->orWhere('sender_id', '=', $reciever_id);
//                       })
//                       ->select('sender_id', DB::raw('MIN(status) as status'), DB::raw('MAX(created_at) as last_message_date'))
//                       ->selectRaw("CASE WHEN sender_id = ".$reciever_id." THEN reciever_id ELSE sender_id END AS user_id")
//                       ->groupBy('sender_id')
//                       ->groupBy('reciever_id')
//                       ->orderBy('status');
//    return $messages;
//  }
//
//  public function getChatsInterlocutors(Builder $messages, int $reciever_id, ?string $status, string $orderByDate) {
//    $users = User::joinSub($messages, 'messages', function ($join) {
//                  $join->on('users.id', '=', 'messages.user_id');
//              })
//              ->select('users.id', 'users.name', 'users.photo', 'messages.sender_id')
//              ->selectRaw("CASE WHEN messages.sender_id = ".$reciever_id." THEN 1 ELSE MIN(messages.status) END AS status")
//              ->selectRaw("MAX(messages.last_message_date) as last_message_date")
//              ->where('users.id', '<>', $reciever_id)
//              ->groupBy('messages.user_id')
//              ->when(is_numeric($status), function ($query) use ($status) {
//                        return $query->where('messages.status', $status);
//                    })
//              ->orderBy('messages.last_message_date', $orderByDate)
//              ->paginate(Config::get('pagination.mainPage'));
//    return $users;
//  }
