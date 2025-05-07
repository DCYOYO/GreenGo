let history = [];
let points = 0;

window.onload = () => {
  checkLoginStatus(() => {
    loadHistory();
    document.getElementById('history-section').classList.remove('d-none');
  });
};

function loadHistory() {
  fetch('../api/backend.php?action=history', { method: 'GET' })
    .then(response => {
      console.log("載入歷史紀錄 - HTTP 狀態碼：", response.status);
      return response.json();
    })
    .then(records => {
      console.log("載入歷史紀錄 - 後端回應：", records);
      history = records.map(record => ({
        transport: record.transport,
        distance: record.distance,
        footprint: record.footprint,
        points: record.points,
        record_time: record.record_time
      }));
      updateUI();
    })
    .catch(error => console.error('載入歷史紀錄失敗:', error));
}

function updateUI() {
  fetch('../api/backend.php?action=user_points', { method: 'GET' })
    .then(response => {
      console.log("獲取點數 - HTTP 狀態碼：", response.status);
      return response.json();
    })
    .then(data => {
      console.log("獲取點數 - 後端回應：", data);
      if (data.status === 'success') {
        points = data.points;
        document.getElementById("points").textContent = points;
      } else {
        console.error('獲取點數失敗:', data.message);
      }
    })
    .catch(error => console.error('獲取點數失敗:', error));
  const list = document.getElementById("history-list");
  list.innerHTML = "";
  history.forEach(record => {
    const li = document.createElement("li");
    li.className = "list-group-item";
    const time = new Date(record.record_time).toLocaleString('zh-TW', {
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    });
    li.textContent = `${time} - ${record.transport} - ${record.distance} 公里 - 碳排放 ${record.footprint.toFixed(2)} kg CO₂ - 獲得 ${record.points} 點`;
    list.appendChild(li);
  });
}