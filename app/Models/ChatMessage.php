<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $table = "t_chat_messages";

    protected $fillable = [
        "Booking_ID",
        "Stud_ID",
        "Prof_ID",
        "Sender",
        "Recipient",
        "Message",
        "Created_At",
        "status",
        "is_read",
        "file_path",
        "file_type",
        "original_name",
    ];

    public $timestamps = false; // Because you use Created_At, not created_at
    protected $dates = ["created_at", "updated_at"];

    public function deliver($request)
    {
        $this->Recipient = $request->input("recipient", null);
        $this->status = "Delivered";
        $this->Created_At = now("Asia/Manila");
    }

    protected $casts = [
        "is_read" => "boolean",
    ];

    /**
     * Scope messages between a student and professor regardless of booking.
     */
    public function scopeBetweenParticipants($query, $studId, $profId)
    {
        return $query->where("Stud_ID", $studId)->where("Prof_ID", $profId);
    }
}
