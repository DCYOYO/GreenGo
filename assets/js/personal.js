document.addEventListener('DOMContentLoaded', function () {
    const uploadToggleBtn = document.getElementById('upload-toggle-btn');
    const editToggleBtn = document.getElementById('edit-toggle-btn');
    const uploadCard = document.getElementById('upload-card');
    const editCard = document.getElementById('edit-card');
    const uploadForm = document.getElementById('upload-form');
    const editForm = document.getElementById('edit-profile-form');
    const uploadMessage = document.getElementById('upload-message');
    const editMessage = document.getElementById('edit-message');
    const profilePictureInput = document.getElementById('profile_picture');
    const imagePreview = document.getElementById('image-preview');

    // 切換上傳表單顯示
    if (uploadToggleBtn) {
        uploadToggleBtn.addEventListener('click', function () {
            uploadCard.classList.toggle('d-none');
            editCard.classList.add('d-none');
        });
    }

    // 切換編輯表單顯示
    if (editToggleBtn) {
        editToggleBtn.addEventListener('click', function () {
            editCard.classList.toggle('d-none');
            uploadCard.classList.add('d-none');
        });
    }

    // 圖片預覽
    if (profilePictureInput) {
        profilePictureInput.addEventListener('change', function (event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreview.src = e.target.result;
                    imagePreview.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // 處理頭像上傳表單
    if (uploadForm) {
        uploadForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const formData = new FormData(uploadForm);
            fetch('/api/backend.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                uploadMessage.classList.remove('d-none', 'alert-success', 'alert-danger');
                if (data.success) {
                    uploadMessage.classList.add('alert-success');
                    uploadMessage.textContent = data.success;
                    if (data.profile_picture) {
                        document.querySelector('#profile-picture-container img').src = data.profile_picture;
                    }
                    uploadCard.classList.add('d-none');
                    uploadForm.reset();
                    imagePreview.classList.add('d-none');
                } else {
                    uploadMessage.classList.add('alert-danger');
                    uploadMessage.textContent = data.error || '上傳失敗';
                }
            })
            .catch(error => {
                uploadMessage.classList.remove('d-none', 'alert-success');
                uploadMessage.classList.add('alert-danger');
                uploadMessage.textContent = '上傳失敗：' + error.message;
            });
        });
    }

    // 處理編輯表單
    if (editForm) {
        editForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const formData = new FormData(editForm);
            fetch('/api/backend.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                editMessage.classList.remove('d-none', 'alert-success', 'alert-danger');
                if (data.success) {
                    editMessage.classList.add('alert-success');
                    editMessage.textContent = data.success;
                    // 更新顯示的資料
                    document.getElementById('bio').textContent = formData.get('bio') || '未設定';
                    document.getElementById('country_code').textContent = formData.get('country_code') || '未設定';
                    document.getElementById('gender').textContent = formData.get('gender') === 'M' ? '男' : (formData.get('gender') === 'F' ? '女' : (formData.get('gender') === 'Other' ? '其他' : '未設定'));
                    document.getElementById('birthdate').textContent = formData.get('birthdate') || '未設定';
                    document.getElementById('activity_level').textContent = formData.get('activity_level') || '未設定';
                    editCard.classList.add('d-none');
                    editForm.reset();
                } else {
                    editMessage.classList.add('alert-danger');
                    editMessage.textContent = data.error || '更新失敗';
                }
            })
            .catch(error => {
                editMessage.classList.remove('d-none', 'alert-success');
                editMessage.classList.add('alert-danger');
                editMessage.textContent = '更新失敗：' + error.message;
            });
        });
    }
});