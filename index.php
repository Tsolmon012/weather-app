<?php
session_start();
require __DIR__ . '/config.php';

$errors = [];
$success = "";

// Form-уудыг боловсруулах
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Гарах
    if ($action === 'logout') {
        session_unset();
        session_destroy();
        header('Location: index.php');
        exit;
    }

    // Бүртгүүлэх
    if ($action === 'register') {
        $last  = trim($_POST['last_name'] ?? '');
        $first = trim($_POST['first_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass1 = $_POST['password'] ?? '';
        $pass2 = $_POST['password_confirm'] ?? '';

        if ($last === '' || $first === '' || $email === '' || $pass1 === '' || $pass2 === '') {
            $errors[] = "Бүх талбарыг бөглөнө үү.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email буруу байна.";
        } elseif ($pass1 !== $pass2) {
            $errors[] = "Нууц үг хоорондоо таарахгүй байна.";
        } else {
            // Email аль хэдийн бүртгэлтэй эсэх
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Энэ email-ээр аль хэдийн бүртгэгдсэн байна.";
            } else {
                $hash = password_hash($pass1, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    "INSERT INTO users (last_name, first_name, email, password_hash)
                     VALUES (?, ?, ?, ?)"
                );
                $stmt->execute([$last, $first, $email, $hash]);

                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_name'] = $first . " " . $last;
                $success = "Амжилттай бүртгэгдлээ, тавтай морил.";
            }
        }
    }

    // Нэвтрэх
    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';

        if ($email === '' || $pass === '') {
            $errors[] = "Email болон нууц үгээ бөглөнө үү.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($pass, $user['password_hash'])) {
                $errors[] = "Нэвтрэх мэдээлэл буруу байна.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . " " . $user['last_name'];
                $success = "Амжилттай нэвтэрлээ.";
            }
        }
    }
}

$loggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="mn">
  <head>
    <meta charset="UTF-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1, viewport-fit=cover"
    />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta
      name="apple-mobile-web-app-status-bar-style"
      content="black-translucent"
    />
    <meta name="format-detection" content="telephone=no" />
    <title>Weather App</title>

    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <style>
      :root {
        --bg: #0b1020;
        --card: #121a33;
        --muted: #9fb0d0;
        --text: #e9eefc;
        --accent: #4da3ff;
        --warn: #ff736a;
        --ok: #3cd287;
        --radius: 18px;
      }
      * { box-sizing: border-box; }
      html, body {
        margin: 0;
        padding: 0;
        background: var(--bg);
        color: var(--text);
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica,
          Arial, "Noto Sans";
        -webkit-text-size-adjust: 100%;
      }
      .wrap {
        min-height: 100vh;
        display: grid;
        place-items: center;
        padding: 16px;
        -webkit-overflow-scrolling: touch;
      }
      .app {
        width: 100%;
        max-width: 460px;
        background: linear-gradient(
          180deg,
          rgba(18, 26, 51, 0.9),
          rgba(18, 26, 51, 0.8)
        );
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: var(--radius);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35),
          0 1px 0 rgba(255, 255, 255, 0.05) inset;
        overflow: hidden;
        -webkit-tap-highlight-color: transparent;
      }
      header {
        padding: 16px 18px 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      }
      header h1 {
        font-size: 18px;
        margin: 0;
        display: flex;
        gap: 10px;
        align-items: center;
      }
      header h1 .dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--accent);
        box-shadow: 0 0 10px var(--accent), 0 0 20px var(--accent);
      }
      .controls {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 8px;
        padding: 14px 16px;
        background: rgba(255, 255, 255, 0.03);
        border-bottom: 1px dashed rgba(255, 255, 255, 0.08);
      }
      .row { display: flex; gap: 8px; }
      input[type="text"] {
        width: 100%;
        padding: 12px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        background: rgba(12, 16, 30, 0.8);
        color: var(--text);
        outline: none;
      }
      input[type="text"]:focus {
        box-shadow: 0 0 0 3px rgba(77, 163, 255, 0.06);
      }
      button {
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        background: rgba(22, 32, 66, 0.85);
        color: var(--text);
        font-weight: 600;
        touch-action: manipulation;
        cursor: pointer;
      }
      button:active { transform: translateY(1px); }
      .ghost { background: transparent; }
      .pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 10px;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.14);
        font-size: 12px;
        color: var(--muted);
      }
      .content {
        padding: 18px;
        display: grid;
        gap: 14px;
      }
      .card {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 14px;
      }
      .main {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
      }
      .temp {
        font-size: 52px;
        font-weight: 700;
        line-height: 1;
        letter-spacing: -0.8px;
      }
      .desc {
        color: var(--muted);
        margin-top: 6px;
        font-size: 14px;
      }
      .city { font-weight: 700; }
      .grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-top: 10px;
      }
      .metric {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(255, 255, 255, 0.03);
        padding: 10px;
        border-radius: 12px;
      }
      .metric b { font-size: 18px; }
      .footer {
        color: var(--muted);
        font-size: 12px;
        text-align: center;
        padding: 0 18px 16px;
      }
      .hidden { display: none !important; }
      .err { color: var(--warn); font-weight: 600; }
      .ok { color: var(--ok); font-weight: 600; }
      .units { display: flex; gap: 6px; }
      .units button { padding: 8px 10px; font-size: 12px; }
      .active {
        border-color: var(--accent);
        box-shadow: 0 0 10px rgba(77, 163, 255, 0.4);
      }

      /* Google user info card */
      .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 12px;
        font-size: 14px;
        color: var(--muted);
      }
      .user-info img {
        width: 48px;
        height: 48px;
        border-radius: 50%;
      }

      /* Login errors/success */
      .errors { color: var(--warn); font-size: 13px; margin-bottom: 8px; }
      .success { color: var(--ok); font-size: 13px; margin-bottom: 8px; }
      .user-row {
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:8px;
      }
      .user-name { font-weight:600; }
      .logout-btn { background:#ff736a; }

      @media (min-width: 480px) {
        header h1 { font-size: 20px; }
        .temp { font-size: 64px; }
      }
    </style>
  </head>
  <body>
    <div class="wrap">
      <div class="app" id="app">
        <header>
          <h1><span class="dot"></span> Цаг агаар</h1>
          <div class="units">
            <button id="btnMetric" class="active" aria-pressed="true">
              °C
            </button>
            <button id="btnImperial" aria-pressed="false">°F</button>
          </div>
        </header>

        <div class="content">

          <!-- MySQL Login/Register card -->
          <div class="card">
            <h2 style="margin:0 0 8px;font-size:16px;">Хэрэглэгчийн нэвтрэлт (MySQL)</h2>

            <?php if ($errors): ?>
              <div class="errors">
                <?php foreach ($errors as $e) echo htmlspecialchars($e) . "<br>"; ?>
              </div>
            <?php endif; ?>

            <?php if ($success): ?>
              <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (!$loggedIn): ?>
              <h3 style="margin:8px 0 4px;font-size:14px;">Нэвтрэх</h3>
              <form method="post" style="margin-bottom:10px;">
                <input type="hidden" name="action" value="login">
                <label style="font-size:13px;">Email</label>
                <input type="email" name="email" required>
                <label style="font-size:13px;">Нууц үг</label>
                <input type="password" name="password" required>
                <button type="submit" style="margin-top:8px;">Нэвтрэх</button>
              </form>

              <hr style="margin:10px 0; border-color:rgba(255,255,255,.08);">

              <h3 style="margin:8px 0 4px;font-size:14px;">Бүртгүүлэх</h3>
              <form method="post">
                <input type="hidden" name="action" value="register">
                <label style="font-size:13px;">Овог</label>
                <input type="text" name="last_name" required>
                <label style="font-size:13px;">Нэр</label>
                <input type="text" name="first_name" required>
                <label style="font-size:13px;">Email</label>
                <input type="email" name="email" required>
                <label style="font-size:13px;">Нууц үг</label>
                <input type="password" name="password" required>
                <label style="font-size:13px;">Нууц үг давтах</label>
                <input type="password" name="password_confirm" required>
                <button type="submit" style="margin-top:8px;">Бүртгүүлэх</button>
              </form>
              <p style="font-size:11px;color:var(--muted);margin:6px 0 0;">
                * Энэ хэсэг MySQL өгөгдлийн сантай холбогдож байна.
              </p>
            <?php else: ?>
              <div class="user-row">
                <div>
                  <div class="user-name"><?= htmlspecialchars($userName) ?></div>
                  <div style="font-size:12px;color:var(--muted);">DB-р нэвтэрсэн хэрэглэгч</div>
                </div>
                <form method="post">
                  <input type="hidden" name="action" value="logout">
                  <button type="submit" class="logout-btn">Гарах</button>
                </form>
              </div>
            <?php endif; ?>
          </div>

          <!-- Google Sign-In section -->
          <div class="card">
            <h2 style="margin: 0 0 8px; font-size: 16px">Google нэвтрэлт</h2>

            <!-- Google Sign-In button -->
            <div
              id="g_id_onload"
              data-client_id="505268840576-choj3sajmanf6p3enfhr62mrd96les3r.apps.googleusercontent.com"
              data-callback="handleCredentialResponse"
              data-auto_prompt="false"
            ></div>

            <div
              class="g_id_signin"
              data-type="standard"
              data-size="large"
              data-theme="outline"
              data-text="sign_in_with"
              data-shape="rectangular"
              data-logo_alignment="left"
            ></div>

            <!-- User info -->
            <div id="userCard" class="user-info hidden">
              <img id="userPicture" alt="User" />
              <div>
                <div id="userName" style="font-weight: 600"></div>
                <div id="userEmail"></div>
              </div>
            </div>
          </div>
          <!-- Google Sign-In section end -->

          <div id="status" class="pill">Бэлэн.</div>

          <div id="card" class="card hidden">
            <div class="main">
              <div>
                <div class="city" id="city">—</div>
                <div class="temp" id="temp">--°</div>
                <div class="desc">
                  <span id="desc">—</span> • <span id="feels">—</span>
                </div>
              </div>
              <img
                id="icon"
                alt="icon"
                width="100"
                height="100"
                class="hidden"
              />
            </div>
            <div class="grid">
              <div class="metric">
                <span>💧 Чийгшил</span><b id="humidity">—</b><span>%</span>
              </div>
              <div class="metric">
                <span>💨 Салхи</span><b id="wind">—</b
                ><span id="windUnit">м/с</span>
              </div>
              <div class="metric">
                <span>📈 Даралт</span><b id="pressure">—</b><span>hPa</span>
              </div>
              <div class="metric">
                <span>🌡️ Макс/Мин</span><b id="minmax">—</b>
              </div>
            </div>
          </div>

          <div class="controls">
            <input
              id="cityInput"
              type="text"
              placeholder="Хот/суурин нэр..."
              list="presetCities"
              inputmode="text"
              autocapitalize="words"
              autocomplete="on"
              autocorrect="off"
              spellcheck="false"
              enterkeyhint="search"
            />
            <datalist id="presetCities">
              <option value="Ulaanbaatar"></option>
              <option value="Darkhan"></option>
              <option value="Erdenet"></option>
              <option value="Khentii"></option>
              <option value="Zuunmod"></option>
              <option value="Tokyo"></option>
              <option value="Seoul"></option>
              <option value="London"></option>
              <option value="New York"></option>
            </datalist>
            <div class="row">
              <button id="btnSearch">Хайх</button>
              <button id="btnGeo" class="ghost">Миний байршлаар</button>
            </div>
          </div>

          <div class="footer">Өгөгдөл: openweathermap.org</div>
        </div>
      </div>
    </div>

    <script>
      /* Google token parse helper */
      function parseJwt(token) {
        const base64Url = token.split(".")[1];
        const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
        const jsonPayload = decodeURIComponent(
          atob(base64)
            .split("")
            .map(function (c) {
              return "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2);
            })
            .join("")
        );
        return JSON.parse(jsonPayload);
      }

      /* Callback called by Google script */
      function handleCredentialResponse(response) {
        const data = parseJwt(response.credential);

        const name = data.name || "Нэр алга";
        const email = data.email || "";
        const picture = data.picture || "";

        els.userName.textContent = name;
        els.userEmail.textContent = email;
        els.userPicture.src = picture;
        els.userPicture.classList.toggle("hidden", !picture);

        els.userCard.classList.remove("hidden");
      }

      /* -------- Weather JS -------- */
      const API_KEY = "298b95adcad4e9a5551a6bdc3d62cc7e",
        API_URL = "https://api.openweathermap.org/data/2.5/weather";
      let units = "metric";
      const els = {
        status: document.getElementById("status"),
        card: document.getElementById("card"),
        city: document.getElementById("city"),
        temp: document.getElementById("temp"),
        desc: document.getElementById("desc"),
        feels: document.getElementById("feels"),
        humidity: document.getElementById("humidity"),
        wind: document.getElementById("wind"),
        windUnit: document.getElementById("windUnit"),
        pressure: document.getElementById("pressure"),
        minmax: document.getElementById("minmax"),
        icon: document.getElementById("icon"),
        btnSearch: document.getElementById("btnSearch"),
        btnGeo: document.getElementById("btnGeo"),
        cityInput: document.getElementById("cityInput"),
        btnMetric: document.getElementById("btnMetric"),
        btnImperial: document.getElementById("btnImperial"),

        // Google user info elements
        userCard: document.getElementById("userCard"),
        userName: document.getElementById("userName"),
        userEmail: document.getElementById("userEmail"),
        userPicture: document.getElementById("userPicture"),
      };

      function setStatus(t, o = false) {
        els.status.textContent = t;
        els.status.classList.toggle("ok", o);
        els.status.classList.toggle("err", !o && /алдаа|error|failed/i.test(t));
      }
      function setUnits(u) {
        units = u;
        const t = u === "metric";
        els.btnMetric.classList.toggle("active", t);
        els.btnImperial.classList.toggle("active", !t);
        els.btnMetric.setAttribute("aria-pressed", t ? "true" : "false");
        els.btnImperial.setAttribute("aria-pressed", !t ? "true" : "false");
        els.windUnit.textContent = t ? "м/с" : "mph";
      }
      async function fetchJson(u) {
        const t = await fetch(u, { mode: "cors" });
        if (!t.ok) throw new Error(`HTTP ${t.status}`);
        return await t.json();
      }
      async function fetchByCity(c) {
        if (!c) {
          setStatus("Хот хоосон байна.", false);
          return;
        }
        setStatus("Уншиж байна...");
        try {
          const u = `${API_URL}?q=${encodeURIComponent(
            c
          )}&appid=${API_KEY}&units=${units}&lang=mn`;
          const t = await fetchJson(u);
          renderWeather(t);
          setStatus("Амжилттай.", true);
        } catch (e) {
          console.error(e);
          setStatus("Алдаа: хот олдсонгүй эсвэл сүлжээний алдаа.", false);
          els.card.classList.add("hidden");
        }
      }
      async function fetchByGeo(a, b) {
        setStatus("Байршлаар шүүж байна...");
        try {
          const t = `${API_URL}?lat=${a}&lon=${b}&appid=${API_KEY}&units=${units}&lang=mn`;
          const e = await fetchJson(t);
          renderWeather(e);
          setStatus("Амжилттай.", true);
        } catch (t) {
          console.error(t);
          setStatus("Алдаа: байршлаар уншиж чадсангүй.", false);
          els.card.classList.add("hidden");
        }
      }
      function renderWeather(d) {
        const c = d.name || "—",
          C = d.sys && d.sys.country ? `, ${d.sys.country}` : "",
          t = Math.round(d.main?.temp ?? 0),
          f = Math.round(d.main?.feels_like ?? 0),
          desc = d.weather && d.weather[0] ? d.weather[0].description : "—",
          h = d.main?.humidity ?? "—",
          w = d.wind?.speed ?? "—",
          p = d.main?.pressure ?? "—",
          tmin = Math.round(d.main?.temp_min ?? 0),
          tmax = Math.round(d.main?.temp_max ?? 0),
          icon = d.weather && d.weather[0]?.icon ? d.weather[0].icon : null;
        els.city.textContent = c + C;
        els.temp.textContent = `${t}°`;
        els.desc.textContent = desc;
        els.feels.textContent = `Мэдрэгдэх: ${f}°`;
        els.humidity.textContent = h;
        els.wind.textContent = w;
        els.pressure.textContent = p;
        els.minmax.textContent = `${tmax}° / ${tmin}°`;
        if (icon) {
          els.icon.src = `https://openweathermap.org/img/wn/${icon}@2x.png`;
          els.icon.classList.remove("hidden");
        } else els.icon.classList.add("hidden");
        els.card.classList.remove("hidden");
      }
      async function getApproxLocationByIP() {
        try {
          const t = await fetch("https://ipapi.co/json/", { mode: "cors" });
          if (!t.ok) throw new Error("IP geo failed");
          const e = await t.json();
          return e && e.latitude && e.longitude
            ? {
                latitude: parseFloat(e.latitude),
                longitude: parseFloat(e.longitude),
              }
            : null;
        } catch (t) {
          console.warn("IP fallback failed", t);
          return null;
        }
      }
      els.btnSearch.addEventListener("click", () =>
        fetchByCity(els.cityInput.value.trim())
      );
      els.cityInput.addEventListener("keydown", (e) => {
        if (e.key === "Enter") fetchByCity(els.cityInput.value.trim());
      });
      els.btnGeo.addEventListener("click", async () => {
        if (!window.isSecureContext) {
          setStatus(
            "Анхаар: Байршил HTTPS шаарддаг. Эхлээд сайтаа HTTPS дээр байршуулна уу.",
            false
          );
          const t = await getApproxLocationByIP();
          if (t) {
            setStatus("IP-аар ойролцоогоор байршлыг олов...", true);
            fetchByGeo(t.latitude, t.longitude);
          }
          return;
        }
        if (!navigator.geolocation) {
          setStatus(
            "Таны браузер байршил дэмжихгүй байна. IP-аар оролдоно...",
            false
          );
          const t = await getApproxLocationByIP();
          if (t) fetchByGeo(t.latitude, t.longitude);
          return;
        }
        setStatus("Байршлыг авах гэж оролдож байна...");
        try {
          navigator.geolocation.getCurrentPosition(
            (pos) => {
              fetchByGeo(pos.coords.latitude, pos.coords.longitude);
            },
            async (t) => {
              console.warn("Geolocation error:", t);
              if (t && (t.code === 1 || t.code === 2 || t.code === 3)) {
                setStatus(
                  "Байршлын зөвшөөрөл хаалттай эсвэл олдсонгүй. IP-аар оролдоно...",
                  false
                );
                const e = await getApproxLocationByIP();
                if (e) fetchByGeo(e.latitude, e.longitude);
                else setStatus("Байршлыг олоход алдаа гарлаа.", false);
              } else setStatus("Байршлыг олоход алдаа гарлаа.", false);
            },
            { enableHighAccuracy: false, timeout: 2e4, maximumAge: 3e5 }
          );
        } catch (t) {
          console.error(t);
          const e = await getApproxLocationByIP();
          if (e) fetchByGeo(e.latitude, e.longitude);
          else setStatus("Байршлыг олоход алдаа гарлаа.", false);
        }
      });
      els.btnMetric.addEventListener("click", () => {
        setUnits("metric");
        const t = els.city.textContent.replace(/,.*$/, "");
        if (t && t !== "—") fetchByCity(t);
      });
      els.btnImperial.addEventListener("click", () => {
        setUnits("imperial");
        const t = els.city.textContent.replace(/,.*$/, "");
        if (t && t !== "—") fetchByCity(t);
      });
      window.addEventListener("load", () => {
        setUnits("metric");
        els.cityInput.value = "Ulaanbaatar";
        fetchByCity("Ulaanbaatar");
      });
    </script>
  </body>
</html>
