const transportOptions = ["步行", "腳踏車", "機車", "汽車", "大眾運輸"];

// 設置後端 API 的基礎路徑（根據部署環境調整）
const BASE_URL = window.location.pathname.includes('pages/') ? '../api/backend.php' : 'api/backend.php';

function showError(elementId, message) {
  const errorDiv = document.getElementById(elementId);
  errorDiv.textContent = message;
  errorDiv.classList.remove('d-none');
}

function clearError(elementId) {
  const errorDiv = document.getElementById(elementId);
  errorDiv.textContent = '';
  errorDiv.classList.add('d-none');
}

function checkLoginStatus(callback) {
  fetch(`${BASE_URL}?action=check_login`, { method: 'GET' })
    .then(response => {
      console.log("檢查登入狀態 - HTTP 狀態碼：", response.status);
      return response.json();
    })
    .then(data => {
      console.log("檢查登入狀態 - 後端回應：", data);
      if (data.status === 'success') {
        showUserInfo(data.username);
        if (callback) callback();
      } else {
        window.location.href = window.location.pathname.includes('pages/') ? '../index.php' : 'index.php';
      }
    })
    .catch(error => {
      console.error('檢查登入狀態失敗:', error);
      window.location.href = window.location.pathname.includes('pages/') ? '../index.php' : 'index.php';
    });
}

function showUserInfo(username) {
  const userInfo = document.getElementById('user-info');
  if (userInfo) {
    document.getElementById('username-display').textContent = username;
    document.getElementById('avatar-initial').textContent = username[0].toUpperCase();
    userInfo.classList.remove('d-none');
  }
}

function logout() {
  fetch(`${BASE_URL}?action=logout`, { method: 'GET' })
  .then(response => {
    console.log("登出 - HTTP 狀態碼：", response.status);
    if (!response.ok) {
      throw new Error(`HTTP 錯誤，狀態碼：${response.status}`);
    }
    return response.json();
  })
  .then(data => {
    console.log("登出 - 後端回應：", data);
    if (data.status === 'success') {
      // 模擬原始 JS 行為：清除任何前端狀態（可選）
      localStorage.removeItem('user'); // 如果原始設計使用 localStorage
      // 重定向到 index.php
      window.location.href = window.location.pathname.includes('pages/') ? '../index.php' : 'index.php';
    } else {
      alert('登出失敗：' + data.message);
    }
  })
  .catch(error => {
    console.error('登出失敗:', error);
    alert('無法連接到後端，請檢查伺服器是否運行');
  });
}

function calculateFootprint(transport, dist) {
  const factors = {
    "步行": 0,
    "腳踏車": 0,
    "機車": 0.079,
    "汽車": 0.104,
    "大眾運輸": 0.078
  };
  return dist * factors[transport];
}

function calculatePoints(transport, dist) {
  let pointsEarned = 0;
  if (transport === "步行") {
    pointsEarned = Math.floor(dist / 0.5);
  } else if (transport === "腳踏車") {
    pointsEarned = Math.floor(dist / 1);
  } else if (transport === "大眾運輸") {
    pointsEarned = Math.floor(dist / 5);
  }
  return pointsEarned;
}

function suggestEcoPath(transport) {
  const suggestion = document.getElementById("eco-suggestion");
  if (suggestion) {
    if (["汽車", "機車"].includes(transport)) {
      suggestion.textContent = "建議改為搭乘大眾運輸或腳踏車，降低碳排放。";
    } else {
      suggestion.textContent = "你選擇了很環保的交通方式，繼續保持！";
    }
    suggestion.classList.remove("d-none");
  }
}

function haversineDistance(p1, p2) {
  const R = 6371;
  const toRad = deg => deg * Math.PI / 180;
  const dLat = toRad(p2.lat - p1.lat);
  const dLon = toRad(p2.lon - p1.lon);
  const lat1 = toRad(p1.lat);
  const lat2 = toRad(p2.lat);
  const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.sin(dLon/2) * Math.sin(dLon/2) * Math.cos(lat1) * Math.cos(lat2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  return R * c;
}