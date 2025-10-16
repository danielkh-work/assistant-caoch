<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserApprovalRequest extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $email;
    public $approvalLink;

    public function __construct($name, $email, $approvalLink)
    {
         \Log::info(['approval email3'=>$approvalLink]);
        $this->name = $name;
        $this->email = $email;
        $this->approvalLink = $approvalLink;
    }

    public function build()
    {
        return $this->subject('User Signup Approval')
                    ->view('emails.user-approval');
    }
}
