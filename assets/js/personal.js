document.addEventListener('DOMContentLoaded', function () {
    console.log('personal.js loaded'); // 確認 JS 檔案是否加載

    const uploadFormCard = document.querySelector('.card:has(#upload-form)');
    const editFormCard = document.querySelector('.card:has(#edit-profile-form)');
    const uploadToggleBtn = document.getElementById('upload-toggle-btn');
    const editToggleBtn = document.getElementById('edit-toggle-btn');

    // 初始化表單顯示狀態
    if (uploadFormCard) uploadFormCard.style.display = 'none';
    if (editFormCard) editFormCard.style.display = 'none';

    // 切換上傳表單顯示
    uploadToggleBtn?.addEventListener('click', () => {
        uploadFormCard.style.display = uploadFormCard.style.display === 'none' ? 'block' : 'none';
    });

    // 切換編輯表單顯示
    editToggleBtn?.addEventListener('click', () => {
        editFormCard.style.display = editFormCard.style.display === 'none' ? 'block' : 'none';
        // 確保城市選單在表單顯示時初始化
        if (editFormCard.style.display === 'block' && countrySelect.value) {
            const selectedCity = citySelect.getAttribute('data-selected');
            populateCities(countrySelect.value, selectedCity);
        }
    });

    // 頭像預覽
    const fileInput = document.getElementById('profile_picture');
    const imagePreview = document.getElementById('image-preview');
    const uploadMessage = document.getElementById('upload-message');
    fileInput?.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            const maxSize = 5 * 1024 * 1024; // 5MB

            if (!allowedTypes.includes(file.type)) {
                uploadMessage.className = 'alert alert-danger';
                uploadMessage.textContent = '僅支援 jpg, png, gif 格式。';
                uploadMessage.classList.remove('d-none');
                imagePreview.classList.add('d-none');
                return;
            }

            if (file.size > maxSize) {
                uploadMessage.className = 'alert alert-danger';
                uploadMessage.textContent = '檔案過大（上限 5MB）。';
                uploadMessage.classList.remove('d-none');
                imagePreview.classList.add('d-none');
                return;
            }

            const reader = new FileReader();
            reader.onload = e => {
                imagePreview.src = e.target.result;
                imagePreview.classList.remove('d-none');
                uploadMessage.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        }
    });

    // 上傳頭像
    const uploadForm = document.getElementById('upload-form');
    uploadForm?.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(uploadForm);

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            uploadMessage.classList.remove('d-none');
            if (data.success) {
                uploadMessage.className = 'alert alert-success';
                uploadMessage.textContent = data.success;
                document.getElementById('profile-picture-container').innerHTML = `
                    <img src="${data.profile_picture}" class="img-fluid rounded-circle mb-3" style="max-width: 150px;" alt="頭像" />
                `;
                uploadFormCard.style.display = 'none';
            } else {
                uploadMessage.className = 'alert alert-danger';
                uploadMessage.textContent = data.error;
            }
        })
        .catch(error => {
            uploadMessage.classList.remove('d-none');
            uploadMessage.className = 'alert alert-danger';
            uploadMessage.textContent = '上傳失敗：' + error.message;
        });
    });

    // 編輯個人資料
    const editForm = document.getElementById('edit-profile-form');
    const editMessage = document.getElementById('edit-message');
    editForm?.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(editForm);
        const countryCode = formData.get('country_code');
        const city = formData.get('city');

        // 客戶端驗證
        if (!countryCode || !city) {
            editMessage.className = 'alert alert-danger';
            editMessage.textContent = '國家代碼和城市為必填欄位。';
            editMessage.classList.remove('d-none');
            return;
        }

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            editMessage.classList.remove('d-none');
            if (data.success) {
                editMessage.className = 'alert alert-success';
                editMessage.textContent = data.success;

                // 更新顯示內容
                document.getElementById('bio').textContent = formData.get('bio') || '未設定';
                document.getElementById('country_code').textContent = formData.get('country_code') || '未設定';
                document.getElementById('city').textContent = formData.get('city') || '未設定';
                const gender = formData.get('gender');
                document.getElementById('gender').textContent = gender === 'M' ? '男' : (gender === 'F' ? '女' : (gender === 'Other' ? '其他' : '未設定'));
                document.getElementById('birthdate').textContent = formData.get('birthdate') || '未設定';
                document.getElementById('activity_level').textContent = formData.get('activity_level') || '未設定';
                editFormCard.style.display = 'none';
            } else {
                editMessage.className = 'alert alert-danger';
                editMessage.textContent = data.error;
            }
        })
        .catch(error => {
            editMessage.classList.remove('d-none');
            editMessage.className = 'alert alert-danger';
            editMessage.textContent = '更新失敗：' + error.message;
        });
    });

    // 國家與城市選單動態聯動
    const countrySelect = document.getElementById('country_code');
    const citySelect = document.getElementById('city');
    const citiesByCountry = {
        TW: ['台北', '台中', '高雄'],
        US: ['New York', 'San Francisco', 'Los Angeles'],
        JP: ['東京', '大阪', '京都']
    };

    function populateCities(countryCode, selectedCity = '') {
        console.log('populateCities called with countryCode:', countryCode, 'selectedCity:', selectedCity);
        if (!citySelect) {
            console.error('citySelect element not found');
            return;
        }

        // 清空現有選項
        citySelect.innerHTML = '<option value="">請選擇城市</option>';
        
        if (citiesByCountry[countryCode]) {
            console.log('Cities found for', countryCode, ':', citiesByCountry[countryCode]);
            citiesByCountry[countryCode].forEach(city => {
                const opt = document.createElement('option');
                opt.value = city;
                opt.textContent = city;
                if (city === selectedCity) opt.selected = true;
                citySelect.appendChild(opt);
            });
            console.log('City options added:', citySelect.options.length, 'options');
        } else {
            console.log('No cities found for countryCode:', countryCode);
        }

        // 強制刷新選單
        citySelect.dispatchEvent(new Event('change'));
    }

    countrySelect?.addEventListener('change', function () {
        console.log('Country changed to:', this.value);
        populateCities(this.value, '');
    });

    // 初始化城市選單
    if (countrySelect) {
        console.log('Initial country value:', countrySelect.value);
        if (countrySelect.value) {
            const selectedCity = citySelect.getAttribute('data-selected');
            console.log('Initializing with country:', countrySelect.value, 'and city:', selectedCity);
            populateCities(countrySelect.value, selectedCity);
        } else {
            console.log('No country selected initially');
        }
    } else {
        console.error('countrySelect element not found');
    }
});