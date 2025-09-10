<?php
include '../config.php';

// ✅ Only professor access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'professor') {
    header('Location: ../login.php');
    exit;
}

// ✅ Fetch colleges
$colleges = [];
$res = $conn->query("SELECT college_id, college_name FROM college ORDER BY college_name");
while ($row = $res->fetch_assoc()) {
    $colleges[] = $row;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Professor Referral & Appointment</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<style>
:root {
  --brand-1:#0f766e;
  --brand-2:#0ea5e9;
}
body {display:flex;min-height:100vh;margin:0;background:#f8f9fa;}
.sidebar {
  background: linear-gradient(180deg,#007bff,#28a745);
  color:white; min-height:100vh; position:fixed; width:250px;
  transition:0.3s; z-index:1000;
}
.sidebar.collapsed {left:-250px;}
.sidebar a {color:white;font-weight:500;}
.sidebar a:hover {background-color: rgba(255,255,255,0.15);}
main {margin-left:250px;transition:0.3s;width:calc(100% - 250px);}
main.full-width {margin-left:0;width:100%;}
.sidebar-toggle {
  position:fixed;top:15px;left:15px;z-index:1100;
  background:#007bff;color:white;border-radius:50%;
  width:45px;height:45px;display:flex;align-items:center;justify-content:center;
}
@media(max-width:768px){
  .sidebar{left:-250px;}
  .sidebar.collapsed{left:0;}
  main{margin-left:0;width:100%;}
}
.chat-wrapper{max-width:800px;margin:30px auto;}
.chat-bubble{background:rgba(255,255,255,0.95);border-radius:18px;padding:14px;margin-bottom:10px;box-shadow:0 2px 8px rgba(0,0,0,0.04);}
.bot{background: linear-gradient(135deg,var(--brand-1),var(--brand-2));color:white;}
.step-hidden{display:none;}
.btn-primary{background:var(--brand-1);border-color:var(--brand-1);}
.btn-accent{background:var(--brand-2);border-color:var(--brand-2);color:white;}
.slot-btn.active{background:var(--brand-2)!important;color:white!important;}
</style>
</head>
<body>

<!-- Sidebar Toggle -->
<button class="btn sidebar-toggle d-md-none" id="sidebarToggle">
  <span class="material-icons">menu</span>
</button>

<!-- Sidebar -->
<aside class="sidebar d-flex flex-column p-3 collapsed d-md-block">
  <div class="profile-image mb-3 text-center">
    <span class="material-icons" style="font-size:48px;">person</span>
  </div>
  <div class="user-name mb-4 text-center">
    <?= htmlspecialchars($_SESSION['username']); ?>
    <a href="profile.php" title="Edit Profile" class="ms-2 text-white text-decoration-none">
      <span class="material-icons" style="vertical-align: middle;">edit</span>
    </a>
  </div>
  <div class="flex-grow-1">
    <a href="referral_form.php" class="btn btn-outline-light w-100 text-start mb-2">Referral</a>
  </div>
  <div class="mt-auto">
    <a href="../logout.php" class="btn btn-danger w-100 text-start">Logout</a>
  </div>
</aside>

<!-- Main Content -->
<main class="flex-grow-1 d-flex flex-column">
<div class="container chat-wrapper">
  <div class="card p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Referral & Appointment — Professor Chat Form</h5>
      <small class="text-muted">Fill student details → choose college & facilitator → select slot → submit</small>
    </div>

    <div id="chatArea">
      <div class="chat-bubble bot p-3 mb-3">
        <strong>Hi Professor!</strong>
        <div class="mt-2">I'll ask a few quick questions to create a referral and book an appointment for the student.</div>
      </div>

      <!-- Step 1: Student Info -->
      <div id="step-student" class="step-hidden">
        <div class="chat-bubble p-3">
          <label class="form-label">Student ID *</label>
          <input id="student_id" type="text" class="form-control mb-2" required>
          <label class="form-label">First name *</label>
          <input id="student_first" class="form-control mb-2" required>
          <label class="form-label">Middle name</label>
          <input id="student_m" class="form-control mb-2">
          <label class="form-label">Last name *</label>
          <input id="student_last" class="form-control mb-2" required>
          <label class="form-label">Course</label>
          <input id="student_course" class="form-control mb-2">
          <label class="form-label">Email *</label>
          <input id="student_email" type="email" class="form-control mb-2" required>
          <div class="d-flex justify-content-end mt-2">
            <button id="toCollege" class="btn btn-primary">Next: Choose college</button>
          </div>
        </div>
      </div>

      <!-- Step 2: College & Facilitator -->
      <div id="step-college" class="step-hidden">
        <div class="chat-bubble bot mb-3">
          Great! Now, let’s pick the student’s <b>college</b> and a <b>facilitator</b>.
        </div>
        <div class="chat-bubble p-3">
          <div class="mb-2">
            <label class="form-label">Select College</label>
            <select id="college_select" class="form-select" required>
              <option value="">-- choose college --</option>
              <?php foreach ($colleges as $c): ?>
                <option value="<?= htmlspecialchars($c['college_id']) ?>"><?= htmlspecialchars($c['college_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Select Facilitator</label>
            <select id="facilitator_select" class="form-select" disabled required>
              <option value="">Select college first</option>
            </select>
          </div>
          <div class="d-flex justify-content-between mt-3">
            <button id="backToStudent" class="btn btn-outline-secondary">← Back</button>
            <button id="toSchedule" class="btn btn-primary" disabled>Next: Choose schedule →</button>
          </div>
        </div>
      </div>

      <!-- Step 3: Schedule & Reason -->
      <div id="step-schedule" class="step-hidden">
        <div class="chat-bubble bot mb-3">
          Awesome! Now, please choose a <b>date</b>, pick an <b>available slot</b>, and provide the <b>reason</b>.
        </div>
        <div class="chat-bubble p-3">
          <div class="mb-3">
            <label for="slotDate" class="form-label">Select Date</label>
            <input type="date" id="slotDate" class="form-control">
          </div>
          <div id="slots_container" class="mb-3">Choose a date to load slots.</div>
          <div class="mb-2">
            <label class="form-label">Purpose / Reason</label>
            <textarea id="reason" class="form-control" rows="2" required></textarea>
          </div>
          <div class="d-flex justify-content-between mt-3">
            <button id="backToCollege" class="btn btn-outline-secondary">← Back</button>
            <button id="submitReferral" class="btn btn-accent" disabled>Submit referral & book →</button>
          </div>
        </div>
      </div>

      <!-- Success -->
      <div id="successBox" class="step-hidden">
        <div class="chat-bubble p-3 bot">
          <strong>All set!</strong>
          <div id="successMessage" class="mt-2"></div>
        </div>
      </div>

    </div>
  </div>
</div>
</main>

<script>
const q=s=>document.querySelector(s);
const show=id=>document.getElementById(id).classList.remove('step-hidden');
const hide=id=>document.getElementById(id).classList.add('step-hidden');

let allSlots={}; // grouped slots by date

document.addEventListener('DOMContentLoaded', ()=>{
  show('step-student');

  // Sidebar toggle
  const sidebar=document.querySelector('.sidebar');
  const main=document.querySelector('main');
  document.getElementById('sidebarToggle').addEventListener('click', ()=>{
    sidebar.classList.toggle('collapsed');
    main.classList.toggle('full-width');
  });

  // Step navigation
  q('#toCollege').addEventListener('click', ()=>{
    if(!q('#student_id').value.trim()||!q('#student_first').value.trim()||!q('#student_last').value.trim()||!q('#student_email').value.trim()){
      alert('Please fill all required student info.');return;
    }
    hide('step-student'); show('step-college');
  });
  q('#backToStudent').addEventListener('click', ()=>{hide('step-college'); show('step-student');});
  q('#toSchedule').addEventListener('click', ()=>{hide('step-college'); show('step-schedule');});
  q('#backToCollege').addEventListener('click', ()=>{hide('step-schedule'); show('step-college');});

  // Load facilitators
  q('#college_select').addEventListener('change', async()=>{
    const college=q('#college_select').value;
    const sel=q('#facilitator_select');
    sel.innerHTML='<option>Loading...</option>'; sel.disabled=true; q('#toSchedule').disabled=true;
    if(!college){ sel.innerHTML='<option>Select college first</option>'; return; }
    try{
      const resp=await fetch('get_facilitators.php?college_id='+encodeURIComponent(college));
      const data=await resp.json();
      sel.innerHTML='<option value="">-- choose facilitator --</option>';
      data.forEach(f=>{
        const opt=document.createElement('option');
        opt.value=f.facilitator_id; opt.textContent=f.first_name+' '+f.last_name;
        sel.appendChild(opt);
      });
      sel.disabled=false;
    }catch(e){ console.error(e); sel.innerHTML='<option>Error loading facilitators</option>'; }
  });

  q('#facilitator_select').addEventListener('change', ()=>{
    q('#toSchedule').disabled=!q('#facilitator_select').value;
    if(q('#facilitator_select').value) loadSlotsForFacilitator(q('#facilitator_select').value);
  });

  q('#submitReferral').addEventListener('click', async()=>{
    const chosen=document.querySelector('.slot-btn.active');
    if(!chosen){alert('Please choose a slot.');return;}
    const payload={
      student_id:q('#student_id').value.trim(),
      student_first:q('#student_first').value.trim(),
      student_m:q('#student_m').value.trim(),
      student_last:q('#student_last').value.trim(),
      student_course:q('#student_course').value.trim(),
      student_email:q('#student_email').value.trim(),
      college_id:q('#college_select').value,
      facilitator_id:q('#facilitator_select').value,
      reason:q('#reason').value.trim(),
      appointment_date:chosen.dataset.date,
      appointment_time:chosen.dataset.time
    };
    q('#submitReferral').disabled=true;
    try{
      const resp=await fetch('submit_referral.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
      const j=await resp.json();
      if(j.status==='success'){
        hide('step-schedule'); show('successBox');
        q('#successMessage').innerHTML=`<p>Referral ID <b>${j.referral_id}</b>, Appointment <b>${payload.appointment_date} ${payload.appointment_time}</b> (ID <b>${j.appointment_id}</b>)</p>`;
      } else {
        alert(j.message||'Error'); q('#submitReferral').disabled=false;
      }
    }catch(e){console.error(e); alert('Server error'); q('#submitReferral').disabled=false;}
  });
});

// ✅ Slots with date picker
async function loadSlotsForFacilitator(fid){
  const container=q('#slots_container'); container.innerHTML='Loading...'; q('#submitReferral').disabled=true;
  try{
    const resp=await fetch('get_schedules.php?facilitator_id='+encodeURIComponent(fid));
    const schedules=await resp.json();
    if(!schedules.length){ container.innerHTML='<div>No slots available.</div>'; return;}

    const slots=[]; const now=new Date();
    for(let d=0; d<30; d++){
      const dt=new Date(); dt.setDate(now.getDate()+d);
      const dayName=dt.toLocaleDateString('en-US',{weekday:'long'});
      if(dayName==='Sunday') continue;
      schedules.forEach(s=>{
        if(s.available_day===dayName){
          let [sh,sm]=s.start_time.split(':').map(Number);
          let [eh,em]=s.end_time.split(':').map(Number);
          let cur=new Date(dt.getFullYear(),dt.getMonth(),dt.getDate(),sh,sm);
          const end=new Date(dt.getFullYear(),dt.getMonth(),dt.getDate(),eh,em);
          while(cur<=end){
            if(cur>now){
              const date=`${dt.getFullYear()}-${String(dt.getMonth()+1).padStart(2,'0')}-${String(dt.getDate()).padStart(2,'0')}`;
              const time=`${String(cur.getHours()).padStart(2,'0')}:${String(cur.getMinutes()).padStart(2,'0')}:00`;
              slots.push({date,time});
            }
            cur=new Date(cur.getTime()+30*60000);
          }
        }
      });
    }

    if(!slots.length){ container.innerHTML='<div>No slots available.</div>'; return;}
    allSlots={}; slots.forEach(s=>{if(!allSlots[s.date])allSlots[s.date]=[];allSlots[s.date].push(s.time);});

    const slotDates=Object.keys(allSlots).sort();
    const dateInput=document.getElementById('slotDate');
    dateInput.min=slotDates[0];
    dateInput.max=slotDates[slotDates.length-1];
    dateInput.value=slotDates[0];
    renderSlots(dateInput.value);

    dateInput.onchange=e=>renderSlots(e.target.value);

  }catch(e){console.error(e); container.innerHTML='Error loading slots.';}
}

function renderSlots(date){
  const container=q('#slots_container');
  container.innerHTML=''; q('#submitReferral').disabled=true;
  if(!allSlots[date]){container.innerHTML='<div>No slots available on this date.</div>';return;}
  const slotBox=document.createElement('div'); slotBox.className="d-flex flex-wrap gap-2";
  allSlots[date].sort().forEach(time=>{
    const btn=document.createElement('button');
    btn.type='button'; btn.className='btn btn-outline-primary slot-btn';
    btn.dataset.date=date; btn.dataset.time=time;
    btn.textContent=new Date('1970-01-01T'+time).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
    btn.addEventListener('click',()=>{
      document.querySelectorAll('.slot-btn').forEach(x=>x.classList.remove('active'));
      btn.classList.add('active'); q('#submitReferral').disabled=false;
    });
    slotBox.appendChild(btn);
  });
  container.appendChild(slotBox);
}
</script>
</body>
</html>
