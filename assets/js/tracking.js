let selectedTransport = "";
let history = [];
let watchId = null;
let positions = [];
let map, directionsService, directionsRenderer;
let startMarker = null, endMarker = null;
let currentDistance = 0;
let currentPoints = 0;

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
  console.log("發送資料：", record);
  fetch('/api/backend.php', {
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
    console.log("儲存紀錄 - HTTP 狀態碼：", response.status);
    return response.json();
  })
  .then(data => {
    console.log('儲存紀錄 - 後端回應：', data);
    if (data.status === 'success') {
      console.log('資料儲存成功');
      alert(`這次${selectedTransport}移動獲得 ${pointsEarned} 點！`);
    } else {
      console.error('儲存資料失敗:', data.message);
      alert('儲存資料失敗：' + data.message);
    }
  })
  .catch(error => {
    console.error('傳送錯誤:', error);
    alert('無法連接到後端，請檢查伺服器是否運行');
  });
  suggestEcoPath(selectedTransport);
  document.getElementById("distance").value = "";
}

function startTracking() {
  if (!selectedTransport){
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
    console.log("收到位置：", pos.coords.latitude, pos.coords.longitude, "目前距離：", currentDistance.toFixed(3), "公里");
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
    console.log("發送資料：", record);
    fetch('/api/backend.php', {
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
      console.log("儲存紀錄 - HTTP 狀態碼：", response.status);
      return response.json();
    })
    .then(data => {
      console.log('儲存紀錄 - 後端回應：', data);
      if (data.status === 'success') {
        console.log('資料儲存成功');
        alert(`這次${selectedTransport}移動獲得 ${pointsEarned} 點！`);
      } else {
        console.error('儲存資料失敗:', data.message);
        alert('儲存資料失敗：' + data.message);
      }
    })
    .catch(error => {
      console.error('傳送錯誤:', error);
      alert('無法連接到後端，請檢查伺服器是否運行');
    });
    suggestEcoPath(selectedTransport);
    currentDistance = 0;
    currentPoints = 0;
  }
}

function initMap() {
  map = new google.maps.Map(document.getElementById("map"), {
    center: { lat: 23.6978, lng: 120.9605 },
    zoom: 8,
  });
  directionsService = new google.maps.DirectionsService();
  directionsRenderer = new google.maps.DirectionsRenderer({ map });
  const startInput = document.getElementById("start");
  const endInput = document.getElementById("end");
  new google.maps.places.Autocomplete(startInput);
  new google.maps.places.Autocomplete(endInput);
  map.addListener("click", (e) => {
    if (!startMarker) {
      startMarker = new google.maps.Marker({
        position: e.latLng,
        map,
        label: "A",
      });
      document.getElementById("start").value = `${e.latLng.lat()}, ${e.latLng.lng()}`;
    } else if (!endMarker) {
      endMarker = new google.maps.Marker({
        position: e.latLng,
        map,
        label: "B",
      });
      document.getElementById("end").value = `${e.latLng.lat()}, ${e.latLng.lng()}`;
    } else {
      alert("已設定起點與終點。請按下『計算地圖距離』。");
    }
  });
}

function calculateRoute() {
  const start = document.getElementById("start").value;
  const end = document.getElementById("end").value;
  if (!start || !end) return alert("請輸入起點與終點！");
  directionsService.route({
    origin: start,
    destination: end,
    travelMode: google.maps.TravelMode.DRIVING
  }, (result, status) => {
    if (status === google.maps.DirectionsStatus.OK) {
      directionsRenderer.setDirections(result);
      const route = result.routes[0].legs[0];
      const distanceKm = route.distance.value / 1000;
      document.getElementById("map-distance").textContent = `計算距離：${distanceKm.toFixed(2)} 公里`;
      if (!selectedTransport) {
        return alert("請先選擇交通方式");
      }
      const footprint = calculateFootprint(selectedTransport, distanceKm);
      const pointsEarned = calculatePoints(selectedTransport, distanceKm);
      const record = {
        transport: selectedTransport,
        distance: distanceKm,
        footprint: footprint,
        points: pointsEarned,
        record_time: new Date().toISOString()
      };
      history.unshift(record);
      console.log("發送資料：", record);
      fetch('/api/backend.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'record',
          transport: selectedTransport,
          distance: distanceKm,
          footprint: footprint,
          points: pointsEarned
        })
      })
      .then(response => {
        console.log("儲存紀錄 - HTTP 狀態碼：", response.status);
        return response.json();
      })
      .then(data => {
        console.log('儲存紀錄 - 後端回應：', data);
        if (data.status === 'success') {
          console.log('資料儲存成功');
          alert(`這次${selectedTransport}移動獲得 ${pointsEarned} 點！`);
        } else {
          console.error('儲存資料失敗:', data.message);
          alert('儲存資料失敗：' + data.message);
        }
      })
      .catch(error => {
        console.error('傳送錯誤:', error);
        alert('無法連接到後端，請檢查伺服器是否運行');
      });
      suggestEcoPath(selectedTransport);
      if (startMarker) {
        startMarker.setMap(null);
        startMarker = null;
      }
      if (endMarker) {
        endMarker.setMap(null);
        endMarker = null;
      }
      document.getElementById("start #map").value = "";
      document.getElementById("end").value = "";
    } else {
      alert("無法計算路線：" + status);
    }
  });
}