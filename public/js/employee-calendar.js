document.addEventListener('DOMContentLoaded',function(){
    const form=document.getElementById('employeeCalendarForm');
    if(!form)return;
    const choices=Array.from(document.querySelectorAll('.calendar-date-choice'));
    const operation=document.getElementById('calendarOperation');
    const selected=()=>choices.filter(choice=>choice.checked && !choice.disabled);
    function update(){
        const dates=selected().map(choice=>choice.value);
        choices.forEach(choice=>choice.closest('.calendar-day').classList.toggle('is-selected',choice.checked && !choice.disabled));
        document.getElementById('calendarSelectionCount').textContent=dates.length+' tanggal dipilih';
        document.getElementById('calendarSelectedDates').textContent=dates.join(', ');
        document.getElementById('calendarOpenAction').disabled=!dates.length;
        document.getElementById('calendarSave').disabled=!dates.length;
        const leave=['izin','cuti','sakit'].includes(operation.value);
        form.setAttribute('action',leave ? employeeCalendarConfig.leaveUrl : employeeCalendarConfig.scheduleUrl);
        document.getElementById('calendarTemplateField').hidden=operation.value!=='shift';
        document.getElementById('calendarTemplate').required=operation.value==='shift';
        document.getElementById('calendarTemplate').disabled=operation.value!=='shift';
        document.getElementById('calendarNote').required=leave;
        document.getElementById('calendarActionHint').textContent=leave ? 'Tanggal tidak berurutan akan dibuat sebagai pengajuan terpisah. Menunggu persetujuan di Day Off.' : 'Jadwal tanggal pilihan akan disimpan. Jadwal lama pada tanggal pilihan diganti jika Anda memiliki izin Delete.';
    }
    choices.forEach(choice=>choice.addEventListener('change',update));
    operation.addEventListener('change',update);
    document.getElementById('calendarSelectAll').addEventListener('click',()=>{choices.forEach(choice=>{if(!choice.disabled)choice.checked=true;});update();});
    document.getElementById('calendarClear').addEventListener('click',()=>{choices.forEach(choice=>choice.checked=false);update();});
    form.addEventListener('submit',event=>{
        if(!selected().length){event.preventDefault();return;}
        if(!window.confirm('Simpan aksi untuk '+selected().length+' tanggal pilihan?'))event.preventDefault();
    });
    update();
    if(employeeCalendarConfig.reopen)new bootstrap.Modal(document.getElementById('calendarActionModal')).show();
});
