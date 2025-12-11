<?php

namespace App\Events;

use App\Models\Term;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TermActivated
{
    use Dispatchable;
    use SerializesModels;

    public Term $term;
    public int $triggeredBy;

    public function __construct(Term $term, int $triggeredBy)
    {
        $this->term = $term;
        $this->triggeredBy = $triggeredBy;
    }
}
