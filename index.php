<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Demo LocalStorage + Redis</title>
</head>
<body>
    <h2>Demo LocalStorage + Redis</h2>
    <button onclick="saveData()">Lưu dữ liệu</button>
    <button onclick="getData()">Lấy dữ liệu từ server</button>
    <pre id="result"></pre>

    <script>
        function saveData() {
            let user = { id: 1, name: "Kiệt" };

            // Lưu vào localStorage
            localStorage.setItem("user", JSON.stringify(user));

            // Gửi lên server để lưu Redis
            fetch("server.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ key: "user:1", value: user })
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById("result").innerText = JSON.stringify(data, null, 2);
            });
        }

        function getData() {
            fetch("server.php?key=user:1")
                .then(res => res.json())
                .then(data => {
                    document.getElementById("result").innerText = JSON.stringify(data, null, 2);
                });
        }
    </script>
</body>
</html>
