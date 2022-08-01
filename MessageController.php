<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;

use Carbon\Carbon;
use App\Events\NewMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Http\Requests\MessageFilterRequest;
use App\Http\Requests\MessageChatRequest;
use App\Http\Requests\MessageRequest;
use App\Http\Requests\MessageUpdateRequest;

use App\Http\Controllers\Services\UserService;
use App\Http\Controllers\Services\MessageService;

class MessageController extends Controller
{

    protected $userService;
    protected $messagerService;

    public function __construct(UserService $userService, MessageService $messageService) {
      $this->userService = $userService;
      $this->messageService = $messageService;
    }

    public function getChat(MessageChatRequest $request)
    {
      $chatIDs = [$this->userService->getUser()->id, $request->input('interlocutor_id')];

      $chatMessages = $this->messageService->getChatMessages($chatIDs);

      $filteredMessages = $this->messageService->filterMessages($chatMessages);

      $allReadFlag = $this->messageService->readUnreadMessages($chatMessages, $chatIDs[0]);

      return response()->json(['filteredMessages' => $filteredMessages, 'allReadFlag' => $allReadFlag], 200);
    }

    public function index(MessageFilterRequest $request)
    {
      $orderByDate = ($request->input('orderByDate')) ? $request->input('orderByDate') : 'desc';
      $status = $request->input('status');
      $reciever_id = $this->userService->getUser()->id;

      $messages =  $this->messageService->getChatsList($reciever_id);
      $users =  $this->messageService->getChatsInterlocutors($messages, $reciever_id, $status, $orderByDate);
      return response()->json($users, 200);
    }

    public function store(MessageRequest $request)
    {
      $user = $this->userService->getUser();
      if($user->id === $request->input('reciever_id')) {
        return response()->json("You can't write messages to yourself", 422);
      }

      $message =  $this->messageService->sendMessage($user, $request);

      broadcast(new NewMessage($message))->toOthers();

      return response()->json($message, 200);
    }

    public function update(MessageUpdateRequest $request, Message $message)
    {
      $user = $this->userService->getUser();
      $message->message = $request->input('message');
      $user->message()->save($message);
      return response()->json($message, 200);
    }

    public function changeStatus(int $messageID)
    {
      // FOR FUTURE implementation
    }

}
