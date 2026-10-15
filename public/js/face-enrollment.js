'use strict';
document.addEventListener('DOMContentLoaded', function () {
    const video = document.getElementById('faceVideo');
    const employee = document.getElementById('faceEmployee');
    const capture = document.getElementById('faceCapture');
    const retry = document.getElementById('faceCameraRetry');
    const submit = document.getElementById('faceEnroll');
    const photo = document.getElementById('facePhoto');
    const status = document.getElementById('faceStatus');
    const result = document.getElementById('faceResult');
    const resultDialog = document.getElementById('faceResultDialog');
    resultDialog.addEventListener('click', function (event) {
        const bounds = resultDialog.getBoundingClientRect();
        if (event.target === resultDialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) resultDialog.close();
    });
    const message = document.getElementById('faceCameraMessage');
    let stream = null, image = null, busy = false, starting = false;
    function update() {
        const enrolled = employee.selectedOptions[0]?.dataset.enrolled === '1';
        status.textContent = employee.value ? (enrolled ? 'Wajah sudah terdaftar' : 'Wajah belum terdaftar') : 'Pilih pegawai terlebih dahulu';
        status.className = 'badge mt-3 badge-' + (employee.value ? (enrolled ? 'success' : 'warning') : 'secondary');
        employee.disabled = busy || !faceEnrollmentConfig.ready;
        submit.disabled = busy || !faceEnrollmentConfig.ready || !employee.value || !image;
        capture.disabled = busy || !stream || video.readyState < 2;
        retry.disabled = busy || starting;
    }
    function reset() {
        image = null;
        photo.classList.add('d-none');
        photo.removeAttribute('src');
        document.getElementById('facePlaceholder').classList.remove('d-none');
        update();
    }
    function feedback(text, ok) {
        result.textContent = text;
        resultDialog.className = 'face-result-dialog ' + (ok ? 'face-result-success' : 'face-result-failed');
        document.getElementById('faceResultTitle').textContent = ok ? 'Pendaftaran berhasil' : 'Pendaftaran belum berhasil';
        document.getElementById('faceResultIcon').textContent = ok ? '✓' : '!';
        if (!resultDialog.open) resultDialog.showModal();
    }
    async function camera() {
        if (starting || busy) return;
        starting = true;
        try {
            if (!navigator.mediaDevices?.getUserMedia) throw Error('Gunakan localhost atau HTTPS untuk mengakses kamera.');
            if (stream) stream.getTracks().forEach(track => track.stop());
            stream = null;
            reset();
            stream = await navigator.mediaDevices.getUserMedia({audio:false,video:{facingMode:'user',width:{ideal:1280},height:{ideal:960}}});
            video.srcObject = stream;
            await video.play();
            message.classList.add('d-none');
        } catch (error) {
            if (stream) stream.getTracks().forEach(track => track.stop());
            stream = null;
            message.textContent = 'Kamera tidak dapat diakses. Izinkan akses kamera, lalu coba lagi. ' + error.message;
            message.classList.remove('d-none');
        } finally { starting = false; update(); }
    }
    employee.addEventListener('change', function () { reset(); result.textContent = ''; });
    video.addEventListener('loadeddata', update);
    retry.addEventListener('click', camera);
    capture.addEventListener('click', function () {
        if (!video.videoWidth) return;
        const canvas = document.createElement('canvas');
        const scale = Math.min(1,1280/video.videoWidth);
        canvas.width = Math.round(video.videoWidth*scale);
        canvas.height = Math.round(video.videoHeight*scale);
        canvas.getContext('2d').drawImage(video,0,0,canvas.width,canvas.height);
        image = canvas.toDataURL('image/jpeg',0.9);
        photo.src = image;
        photo.classList.remove('d-none');
        document.getElementById('facePlaceholder').classList.add('d-none');
        result.textContent = '';
        update();
    });
    submit.addEventListener('click', async function () {
        if (submit.disabled || busy) return;
        if (employee.selectedOptions[0]?.dataset.enrolled === '1' && !window.confirm('Ganti wajah yang sudah terdaftar untuk pegawai ini?')) return;
        busy = true;
        submit.textContent = 'Mendaftarkan…';
        update();
        try {
            const response = await fetch(faceEnrollmentConfig.url,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':faceEnrollmentConfig.csrf},body:JSON.stringify({employee_id:employee.value,photo:image})});
            const data = await response.json().catch(() => null);
            if (!response.ok) throw Error(data?.errors ? Object.values(data.errors).flat().join(' ') : data?.message || 'Pendaftaran gagal. Periksa sesi login dan koneksi, lalu coba lagi.');
            if (!data?.message) throw Error('Respons server tidak valid. Silakan muat ulang halaman.');
            employee.selectedOptions[0].dataset.enrolled = '1';
            feedback(data.message,true);
            reset();
        } catch (error) { feedback(error.message,false); }
        finally { busy = false; submit.textContent = 'Daftarkan Wajah'; update(); }
    });
    window.addEventListener('pagehide', () => stream?.getTracks().forEach(track => track.stop()));
    update();
    camera();
});
