// 圖像預覽
document.getElementById('profile_picture')?.addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (file && ['image/jpeg', 'image/png', 'image/gif'].includes(file.type)) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const imgPreview = document.getElementById('image-preview');
            imgPreview.src = e.target.result;
            imgPreview.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    } else {
        const messageDiv = document.getElementById('upload-message');
        messageDiv.className = 'alert alert-danger';
        messageDiv.textContent = '請選擇有效的圖片格式（jpg, png, gif）。';
        messageDiv.classList.remove('d-none');
    }
});

// 上傳頭像
document.getElementById('upload-form')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    try {
        const res = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        const contentType = res.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await res.text();
            throw new Error('回應不是有效的 JSON: ' + text.substring(0, 50));
        }
        const result = await res.json();
        const messageDiv = document.getElementById('upload-message');
        if (result.success) {
            messageDiv.className = 'alert alert-success';
            messageDiv.textContent = result.success;
            const profilePic = document.querySelector('#profile-picture-container img');
            if (profilePic) {
                profilePic.src = result.profile_picture + '?t=' + new Date().getTime();
            }
        } else {
            messageDiv.className = 'alert alert-danger';
            messageDiv.textContent = result.error || '圖片上傳失敗，請稍後再試。';
        }
        messageDiv.classList.remove('d-none');
    } catch (err) {
        console.error('上傳錯誤：', err);
        const messageDiv = document.getElementById('upload-message');
        messageDiv.className = 'alert alert-danger';
        messageDiv.textContent = '上傳過程中發生錯誤：' + err.message;
        messageDiv.classList.remove('d-none');
    }
});

// 編輯個人資料
document.getElementById('edit-profile-form')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    try {
        const res = await fetch('/personal', {
            method: 'POST',
            body: formData
        });
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        const contentType = res.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await res.text();
            throw new Error('回應不是有效的 JSON: ' + text.substring(0, 50));
        }
        const result = await res.json();
        const messageDiv = document.getElementById('edit-message');
        if (result.success) {
            messageDiv.className = 'alert alert-success';
            messageDiv.textContent = result.success;
        } else {
            messageDiv.className = 'alert alert-danger';
            messageDiv.textContent = result.error || '資料更新失敗';
        }
        messageDiv.classList.remove('d-none');
    } catch (err) {
        console.error('個人資料提交錯誤：', err);
        const messageDiv = document.getElementById('edit-message');
        messageDiv.className = 'alert alert-danger';
        messageDiv.textContent = '提交過程中發生錯誤：' + err.message;
        messageDiv.classList.remove('d-none');
    }
});

// 按鈕切換表單顯示
document.getElementById('upload-toggle-btn')?.addEventListener('click', function () {
    const uploadFormCard = document.querySelector('#upload-form').closest('.card');
    const editFormCard = document.querySelector('#edit-profile-form').closest('.card');
    uploadFormCard.style.display = uploadFormCard.style.display === 'none' ? 'block' : 'none';
    editFormCard.style.display = 'none';
});

document.getElementById('edit-toggle-btn')?.addEventListener('click', function () {
    const editFormCard = document.querySelector('#edit-profile-form').closest('.card');
    const uploadFormCard = document.querySelector('#upload-form').closest('.card');
    editFormCard.style.display = editFormCard.style.display === 'none' ? 'block' : 'none';
    uploadFormCard.style.display = 'none';
});