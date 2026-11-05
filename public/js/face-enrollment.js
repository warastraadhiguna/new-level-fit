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
    const pose = document.getElementById('facePose'), sampleList = document.getElementById('faceSampleList');
    const poseNames = {front:'Depan',left:'Sedikit kiri',right:'Sedikit kanan',additional:'Tambahan'};
    let samples = [], samplesLoaded = false, loadVersion = 0;
    let stream = null, image = null, busy = false, starting = false;
    function update() {
        const enrolled = samples.some(sample => sample.compatible);
        const count = samplesLoaded ? samples.length : Number(employee.selectedOptions[0]?.dataset.count || 0);
        status.textContent = employee.value ? (samplesLoaded ? count + '/5 contoh · ' + (enrolled ? (count < 3 ? 'tambahkan hingga 3 contoh' : 'siap digunakan') : 'belum ada contoh model aktif') : 'Memuat contoh wajah…') : 'Pilih pegawai terlebih dahulu';
        status.className = 'badge mt-3 badge-' + (employee.value ? (enrolled ? 'success' : 'warning') : 'secondary');
        employee.disabled = busy || !faceEnrollmentConfig.ready;
        submit.disabled = busy || !faceEnrollmentConfig.ready || !employee.value || !image || !samplesLoaded || count >= 5;
        capture.disabled = busy || !stream || video.readyState < 2;
        retry.disabled = busy || starting;
        pose.disabled = busy;
        sampleList.querySelectorAll('button').forEach(button => button.disabled = busy);
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
    function renderSamples(data) {
        samples = data.samples;
        samplesLoaded = true;
        const option = employee.selectedOptions[0];
        option.dataset.count = String(samples.length);
        option.dataset.enrolled = samples.some(sample => sample.compatible) ? '1' : '0';
        sampleList.replaceChildren();
        for (const sample of samples) {
            const row = document.createElement('div');row.className = 'd-flex justify-content-between align-items-center border-bottom py-2';
            const label = document.createElement('span');label.textContent = 'Contoh ' + sample.slot + ' · ' + (poseNames[sample.pose] || sample.pose) + (sample.compatible ? '' : ' (model lama)');
            const remove = document.createElement('button');remove.type = 'button';remove.className = 'btn btn-sm btn-outline-danger';remove.textContent = 'Hapus';
            remove.addEventListener('click', () => removeSample(sample.id));row.append(label, remove);sampleList.append(row);
        }
        pose.value = ['front','left','right'].find(value => !samples.some(sample => sample.pose === value)) || 'additional';
        update();
    }
    async function parseResponse(response) {
        const data = await response.json().catch(() => null);
        if (!response.ok) throw Error(data?.errors ? Object.values(data.errors).flat().join(' ') : data?.message || 'Permintaan gagal. Periksa sesi login dan koneksi.');
        if (!Array.isArray(data?.samples)) throw Error('Respons contoh wajah tidak valid. Muat ulang halaman.');
        return data;
    }
    async function loadSamples() {
        const version = ++loadVersion;
        samples = [];samplesLoaded = false;sampleList.replaceChildren();update();
        if (!employee.value || !faceEnrollmentConfig.ready) return;
        try {
            const response = await fetch(faceEnrollmentConfig.samplesUrl + '?employee_id=' + encodeURIComponent(employee.value), {headers:{Accept:'application/json'},cache:'no-store'});
            const data = await parseResponse(response);
            if (version === loadVersion) renderSamples(data);
        } catch (error) { if (version === loadVersion) feedback(error.message, false); }
    }
    async function removeSample(id) {
        if (busy || !window.confirm(samples.length === 1 ? 'Hapus contoh terakhir? Pegawai tidak dapat absen sampai wajah didaftarkan lagi.' : 'Hapus contoh wajah ini?')) return;
        busy = true;update();
        try {
            const response = await fetch(faceEnrollmentConfig.samplesUrl + '/' + id,{method:'DELETE',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':faceEnrollmentConfig.csrf},body:JSON.stringify({employee_id:employee.value})});
            const data = await parseResponse(response);renderSamples(data);feedback(data.message, true);reset();
        } catch (error) { feedback(error.message, false); }
        finally { busy = false;update(); }
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
    employee.addEventListener('change', function () { reset(); result.textContent = ''; loadSamples(); });
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
        busy = true;
        submit.textContent = 'Mendaftarkan…';
        update();
        try {
            const response = await fetch(faceEnrollmentConfig.url,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':faceEnrollmentConfig.csrf},body:JSON.stringify({employee_id:employee.value,photo:image,pose:pose.value})});
            const data = await parseResponse(response);
            if (!data?.message) throw Error('Respons server tidak valid. Silakan muat ulang halaman.');
            renderSamples(data);
            feedback(data.message,true);
            reset();
        } catch (error) { feedback(error.message,false); }
        finally { busy = false; submit.textContent = 'Tambah Contoh Wajah'; update(); }
    });
    window.addEventListener('pagehide', () => stream?.getTracks().forEach(track => track.stop()));
    update();
    camera();
});
