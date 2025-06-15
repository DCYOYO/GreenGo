let selectedTransport = "";
let history = [];
let map, directionsService, directionsRenderer;
let startMarker = null, endMarker = null;
let markers = [];
let watchId = null;
let currentDistance = 0;
let currentPoints = 0;
let positions = [];

window.onload = () => {
  setupTransportButtons();
};

function setupTransportButtons() {
  const container = document.getElementById("transport-options");
  transportOptions.forEach(option => {
    const btn = document.createElement("button");
    btn.className = "btn btn-outline-success m-1 transport-btn";
    btn.textContent = option;
    btn.onclick = () => {
      document.querySelectorAll('.transport-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add("active");
      selectedTransport = option;
    };
    container.appendChild(btn);
  });
}

function handleSubmit() {
  const dist = parseFloat(document.getElementById("distance").value);
  if (!selectedTransport || isNaN(dist)) {
    alert("請選擇交通方式並輸入有效的距離。");
    return;
  }
  const footprint = calculateFootprint(selectedTransport, dist);
  const pointsEarned = calculatePoints(selectedTransport, dist);
  const record = {
    transport: selectedTransport,
    distance: dist,
    footprint: footprint,
    points: pointsEarned,
    record_time: new Date().toISOString()
  };
  history.unshift(record);
  fetch('/api/backend', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'record',
      transport: selectedTransport,
      distance: dist,
      footprint: footprint,
      points: pointsEarned
    })
  })
    .then(response => {
      return response.json();
    })
    .then(data => {
      if (data.status === 'success') {
        alert(`這次${selectedTransport}移動獲得 ${pointsEarned} 點！`);
        suggestEcoPath(selectedTransport);
      } else {
        alert('儲存資料失敗：' + data.message);
      }
    })
    .catch(error => {
      alert('無法連接到後端，請檢查伺服器是否運行');
    });

  document.getElementById("distance").value = "";
}

function startTracking() {
  if (!selectedTransport) {
    return alert("請先選擇交通方式。");
  }
  positions = [];
  currentDistance = 0;
  currentPoints = 0;
  watchId = navigator.geolocation.watchPosition(pos => {
    const newPosition = {
      lat: pos.coords.latitude,
      lon: pos.coords.longitude,
      time: Date.now()
    };
    positions.push(newPosition);
    if (positions.length >= 2) {
      const lastPosition = positions[positions.length - 2];
      const newDistance = haversineDistance(lastPosition, newPosition);
      currentDistance += newDistance;
      const newPoints = calculatePoints(selectedTransport, currentDistance);
      if (newPoints > currentPoints) {
        const pointsGained = newPoints - currentPoints;
        alert(`你剛獲得 ${pointsGained} 點！目前總點數：${newPoints}`);
        currentPoints = newPoints;
      }
    }
  }, err => {
    alert("無法取得位置資訊: " + err.message);
  }, { enableHighAccuracy: true });
}

function stopTracking() {
  if (watchId !== null) {
    navigator.geolocation.clearWatch(watchId);
    watchId = null;
    let totalDistance = 0;
    for (let i = 1; i < positions.length; i++) {
      totalDistance += haversineDistance(positions[i - 1], positions[i]);
    }
    totalDistance = parseFloat(totalDistance.toFixed(3));
    const footprint = calculateFootprint(selectedTransport, totalDistance);
    const pointsEarned = calculatePoints(selectedTransport, totalDistance);
    const record = {
      transport: selectedTransport,
      distance: totalDistance,
      footprint: footprint,
      points: pointsEarned,
      record_time: new Date().toISOString()
    };
    history.unshift(record);
    fetch('/api/backend', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'record',
        transport: selectedTransport,
        distance: totalDistance,
        footprint: footprint,
        points: pointsEarned
      })
    })
      .then(response => {
        return response.json();
      })
      .then(data => {
        if (data.status === 'success') {
          alert(`這次${selectedTransport}移動獲得 ${pointsEarned} 點！`);
          suggestEcoPath(selectedTransport);
        } else {
          alert('儲存資料失敗：' + data.message);
        }
      })
      .catch(error => {
        alert('無法連接到後端，請檢查伺服器是否運行');
      });

    currentDistance = 0;
    currentPoints = 0;
  }
}

// 初始化地圖並設置相關功能
function initMap() {
  // 創建 Google 地圖實例，中心點設為台灣（經緯度），初始縮放等級為 8
  map = new google.maps.Map(document.getElementById("map"), {
    center: { lat: 23.6978, lng: 120.9605 },
    zoom: 8,
  });

  // 初始化路線規劃服務
  directionsService = new google.maps.DirectionsService();
  // 初始化路線渲染器，並將其綁定到地圖
  directionsRenderer = new google.maps.DirectionsRenderer({ map });

  // 獲取起點和終點輸入框的 DOM 元素
  const startInput = document.getElementById("start");
  const endInput = document.getElementById("end");

  // 為起點和終點輸入框添加 Google Places 自動完成功能
  new google.maps.places.Autocomplete(startInput);
  new google.maps.places.Autocomplete(endInput);

  // 監聽地圖的點擊事件，允許使用者通過點擊地圖設置起點和終點
  map.addListener("click", (e) => {
    // 如果尚未設置起點標記
    if (!startMarker) {
      // 在點擊位置創建起點標記，標記為 "A"
      startMarker = new google.maps.Marker({
        position: e.latLng,
        map,
        label: "A",
      });
      // 將標記添加到 markers 陣列
      markers.push(startMarker);
      // 將點擊位置的經緯度填入起點輸入框
      document.getElementById("start").value = `${e.latLng.lat()}, ${e.latLng.lng()}`;
    }
    // 如果起點已設置但尚未設置終點標記
    else if (!endMarker) {
      // 在點擊位置創建終點標記，標記為 "B"
      endMarker = new google.maps.Marker({
        position: e.latLng,
        map,
        label: "B",
      });
      // 將標記添加到 markers 陣列
      markers.push(endMarker);
      // 將點擊位置的經緯度填入終點輸入框
      document.getElementById("end").value = `${e.latLng.lat()}, ${e.latLng.lng()}`;
    }
    // 如果起點和終點都已設置，提示使用者計算距離
    else {
      alert("已設定起點與終點。請按下『計算地圖距離』。");
    }
  });
}

// 清除地圖上的標記、路線和距離顯示
function clearMap() {
  // 清除路線
  directionsRenderer.setDirections({ routes: [] });
  // 清除所有標記
  markers.forEach(marker => marker.setMap(null));
  markers = [];
  startMarker = null;
  endMarker = null;
  // 清空距離顯示
  document.getElementById("map-distance").textContent = "";
  // 清空起點和終點輸入框
  document.getElementById("start").value = "";
  document.getElementById("end").value = "";
}

// 計算路線並處理結果
function calculateRoute() {
  // 獲取起點和終點輸入框的值
  const start = document.getElementById("start").value;
  const end = document.getElementById("end").value;

  // 檢查是否輸入起點和終點，若無則提示
  if (!start || !end) {
    alert("請輸入起點與終點！");
    return;
  }

  // 檢查是否選擇了交通方式
  if (!selectedTransport) {
    alert("請先選擇交通方式！");
    return;
  }

  // 使用 Google Directions API 計算駕車路線
  directionsService.route(
    {
      origin: start, // 起點
      destination: end, // 終點
      travelMode: google.maps.TravelMode.DRIVING, // 交通方式設為駕車
    },
    (result, status) => {
      // 檢查路線計算是否成功
      if (status === google.maps.DirectionsStatus.OK) {
        // 將路線顯示在地圖上
        directionsRenderer.setDirections(result);

        // 獲取路線的第一段路徑資料
        const route = result.routes[0].legs[0];
        // 將距離從公尺轉換為公里
        const distanceKm = route.distance.value / 1000;

        // 顯示計算出的距離
        document.getElementById("map-distance").textContent = `計算距離：${distanceKm.toFixed(2)} 公里`;

        // 計算碳足跡（假設函數已定義）
        const footprint = calculateFootprint(selectedTransport, distanceKm);
        // 計算獲得的積分（假設函數已定義）
        const pointsEarned = calculatePoints(selectedTransport, distanceKm);

        // 創建記錄物件，包含交通方式、距離、碳足跡、積分及記錄時間
        const record = {
          transport: selectedTransport,
          distance: distanceKm,
          footprint: footprint,
          points: pointsEarned,
          record_time: new Date().toISOString(),
        };

        // 將記錄添加到歷史記錄陣列的開頭
        history.unshift(record);

        // 發送記錄到後端 API
        fetch('/api/backend', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'record',
            transport: selectedTransport,
            distance: distanceKm,
            footprint: footprint,
            points: pointsEarned,
          }),
        })
          .then(response => {
            // 記錄 HTTP 狀態碼
            return response.json();
          })
          .then(data => {
            // 處理後端回應
            if (data.status === 'success') {
              // 提示使用者獲得的積分
              alert(`這次${selectedTransport}移動獲得 ${pointsEarned} 點！`);
            } else {
              alert('儲存資料失敗：' + data.message);
            }
          })
          .catch(error => {
            // 處理傳送錯誤
            alert('無法連接到後端，請檢查伺服器是否運行');
          });

        // 根據交通方式建議更環保的路徑（假設函數已定義）
        suggestEcoPath(selectedTransport);
      } else {
        // 提示路線計算失敗的原因
        alert("無法計算路線：" + status);
      }

      // 清除地圖上的標記、路線和輸入框
      clearMap();
    }
  );
}
