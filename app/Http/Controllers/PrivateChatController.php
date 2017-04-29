<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\PrivateMessageEvent;
use App\Models\User;
use App\Models\ChatRoom;
use App\Models\Message;
use App\Models\Receiver;
use App\Models\RoomMember;

class PrivateChatController extends Controller
{
	/**
     * Create a new controller instance.
     *
     * @return void
     */
	public function __construct()
	{
		$this->middleware('auth');
	}

    public function index()
    {

    }

    public function index($receiverId)
    {
        $senderUserId = auth()->user()->id;
        $roomMembers = [$receiverId, $senderUserId];
        sort($roomMembers);
        $roomMembers = implode($roomMembers, ',');
        
        $chatRoom = ChatRoom::where('user_ids', $roomMembers)->first();
        if(is_null($chatRoom)) {
            $chatRoom = new ChatRoom;
            $chatRoom->room_type = 'private';
            $chatRoom->user_ids = $roomMembers;
            $chatRoom->save();
        }

        $messages = Message::where('chat_room_id', $chatRoom->id)->get();
        return view('private-chat.form', compact('chatRoom', 'messages'));
    }

    public function store(ChatRoom $chatroom)
    {
        $senderId = auth()->user()->id;
        $roomMembers = collect(explode(',', $chatroom->user_ids));
        $roomMembers->forget($roomMembers->search($senderId));
        $receiverId = $roomMembers->first();

        $message = new Message;
        $message->chat_room_id = $chatroom->id;
        $message->sender_id = $senderId;
        $message->message = request('message');
        $message->save();

        $receiver = new Receiver;
        $receiver->message_id = $message->id;
        $receiver->receiver_id = $receiverId;

        if($receiver->save()) {
            event(new PrivateMessageEvent($message));
            //broadcast(new ShippingStatusUpdated($update))->toOthers();
            return $message->room_id;
        } else {
            dd('Something went wrong!!');
        }
    }
}
