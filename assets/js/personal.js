const BASE_URL = window.location.pathname.includes('pages/') ? '../api/backend.php' : 'api/backend.php';

window.onload = () => {
  checkLoginStatus(() => {
    loadPersonalInfo();
    document.getElementById('personal-section').classList.remove('d-none');
  });
};

function loadPersonalInfo() {
  fetch(`${BASE_URL}?action=personal`, { method: 'GET' })
    .then(response => {
      console.log("載入個人主頁 - HTTP 狀態碼：", response.status);
      return response.json();
    })
    .then(data => {
      console.log("載入個人主頁 - 後端回應：", data);
      if (data.status === 'success') {
        renderPersonalInfo(data.user);
      } else {
        showError('personal-error', data.message || '無法載入個人主頁資訊');
      }
    })
    .catch(error => {
      console.error('載入個人主頁失敗:', error);
      showError('personal-error', '無法連接到後端，請檢查伺服器是否運行');
    });
}

function renderPersonalInfo(user) {
  const section = document.getElementById('personal-section');
  if (!user) {
    section.innerHTML = `
      <div class="card-body" style="border-radius: 15px;">
        <h5 class="card-title">個人主頁</h5>
        <div class="alert alert-warning" role="alert">
          尚未設定個人主頁資訊。
        </div>
      </div>
    `;
    return;
  }

  const profilePicture = user.profile_picture ? `
    <img src="${user.profile_picture}" alt="頭像" class="rounded-circle me-4 mb-3 mb-md-0" style="width: 100px; height: 100px; object-fit: cover;">
  ` : `
    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-4 mb-3 mb-md-0" style="width: 100px; height: 100px;">
      <span class="text-white fw-bold">${user.username[0].toUpperCase()}</span>
    </div>
  `;

  const genderText = user.gender === 'M' ? '男' : user.gender === 'F' ? '女' : user.gender === 'Other' ? '其他' : '未填寫';
  const countryCity = (user.country_code || user.city) ? `${user.country_code || '未填寫'} / ${user.city || '未填寫'}` : '未填寫';

  section.innerHTML = `
    <div class="card-body" style="border-radius: 15px;">
      <h5 class="card-title">個人主頁</h5>
      <div class="d-flex flex-column flex-md-row align-items-center mb-4">
        ${profilePicture}
        <div>
          <h3 class="h5">${user.username}</h3>
          <p class="text-muted">總積分：${user.total_points} 點 | 總碳足跡：${user.total_footprint} kg</p>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <strong>簡介：</strong> ${user.bio || '未填寫'}
        </div>
        <div class="col-md-6 mb-3">
          <strong>國家/城市：</strong> ${countryCity}
        </div>
        <div class="col-md-6 mb-3">
          <strong>性別：</strong> ${genderText}
        </div>
        <div class="col-md-6 mb-3">
          <strong>生日：</strong> ${user.birthdate || '未填寫'}
        </div>
        <div class="col-md-6 mb-3">
          <strong>活躍程度：</strong> ${user.activity_level || '未填寫'}
        </div>
        <div class="col-md-6 mb-3">
          <strong>上次登入：</strong> ${user.last_login || '未登入'}
        </div>
      </div>
    </div>
  `;
}