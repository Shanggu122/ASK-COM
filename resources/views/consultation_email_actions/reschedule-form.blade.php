<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Reschedule Consultation</title>
  <!-- System theme font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --clr-bg:#f5f7fa; /* matches app light background */
    --clr-surface:#ffffff;
    --clr-border:#e2e8f0;
    --clr-text:#12372a;
    --clr-muted:#555;
    --clr-accent:#12372a;
    --clr-accent-hover:#0d2a1f;
    --clr-chip-bg:#e2e8f0;
    --clr-chip-bg-hover:#d7dee6;
    --clr-chip-active:#12372a;
  }
  body{font-family:'Poppins',Arial,sans-serif;background:var(--clr-bg);margin:0;padding:48px 24px;color:var(--clr-text);}
  .card{max-width:600px;margin:0 auto;background:var(--clr-surface);border:1px solid var(--clr-border);border-radius:14px;padding:34px 36px;box-shadow:0 8px 22px -4px rgba(0,0,0,.08),0 2px 6px rgba(0,0,0,.05);} 
  h1{margin:0 0 18px;font-size:26px;letter-spacing:.5px;font-weight:600;}
  label{display:block;font-weight:600;margin:22px 0 8px;font-size:14px;}
  textarea{width:100%;padding:12px 14px;border:1px solid #cdd5df;border-radius:8px;font-size:14px;font-family:inherit;resize:vertical;min-height:110px;}
  textarea:focus{outline:2px solid var(--clr-accent);outline-offset:1px;}
  button.primary{margin-top:26px;background:var(--clr-accent);color:#fff;border:none;padding:13px 22px;border-radius:8px;cursor:pointer;font-size:14px;font-weight:600;letter-spacing:.25px;box-shadow:0 2px 4px rgba(0,0,0,.15);} 
  button.primary:hover{background:var(--clr-accent-hover);} 
  button.primary:focus{outline:3px solid #0f5c44;}
  .error{color:#c53030;font-size:13px;margin-top:6px;font-weight:500;}
  .meta{font-size:13px;color:var(--clr-muted);line-height:1.4;margin-bottom:6px;}
  /* Date chips */
  .chips-wrapper{margin-top:10px;display:flex;flex-wrap:wrap;gap:8px;}
  button.date-chip{background:var(--clr-chip-bg);color:var(--clr-text);border:1px solid var(--clr-border);padding:8px 16px;border-radius:22px;font-size:12px;font-weight:500;cursor:pointer;line-height:1;position:relative;transition:.18s background,.18s color,.18s transform,.18s box-shadow;box-shadow:0 1px 2px rgba(0,0,0,.06);} 
  button.date-chip:hover{background:var(--clr-chip-bg-hover);} 
  button.date-chip:active{transform:translateY(1px);} 
  button.date-chip:focus{outline:3px solid #94c2b3;outline-offset:1px;} 
  button.date-chip.active{background:var(--clr-chip-active);color:#fff;border-color:var(--clr-chip-active);box-shadow:0 2px 6px rgba(0,0,0,.18);} 
  .helper-text{margin-top:8px;font-size:12px;color:var(--clr-muted);}
  @media (max-width:640px){body{padding:32px 14px;} .card{padding:28px 22px;}}
</style>
</head><body>
  <div class="card">
    <h1>Reschedule Consultation</h1>
    <p class="meta"><strong>Student:</strong> {{ $booking->student_name }}<br><strong>Subject:</strong> {{ $booking->subject_name }}<br><strong>Current Date:</strong> {{ $booking->Booking_Date }}</p>
    @if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ URL::signedRoute('consultation.email.reschedule.submit',[ 'bookingId'=>$booking->Booking_ID,'profId'=>$booking->Prof_ID ]) }}">
      @csrf
      <label for="new_date">New Date</label>
      <input type="hidden" id="new_date" name="new_date" value="{{ old('new_date') }}" required>
  <div class="helper-text">Select an available date:</div>
  <div id="quickDates" class="chips-wrapper">
        @if(!empty($allowedDates))
          @foreach($allowedDates as $d)
            <button type="button" class="date-chip" data-iso="{{ $d['iso'] }}">{{ $d['display'] }}</button>
          @endforeach
        @else
          <span style="font-size:12px;color:#c53030;">No scheduled future availability found.</span>
        @endif
      </div>    
      <label for="reason">Reason (optional)</label>
      <textarea id="reason" name="reason" rows="3">{{ old('reason') }}</textarea>
  <button type="submit" class="primary">Submit Reschedule</button>
    </form>
    <script>
      (function(){
        const input = document.getElementById('new_date');
        const chips = document.querySelectorAll('.date-chip');
        function resetChips(){
          chips.forEach(c=>{
    c.classList.remove('active');
          });
        }
        chips.forEach(ch => ch.addEventListener('click', () => {
          resetChips();
          ch.classList.add('active');
          input.value = ch.dataset.iso;
        }));
        // If previously chosen value exists, mark it
        if(input.value){
          const pre = Array.from(chips).find(c=>c.dataset.iso===input.value);
          if(pre){ pre.click(); }
        }
      })();
    </script>
  </div>
</body></html>
