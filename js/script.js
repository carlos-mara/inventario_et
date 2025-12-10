// Funciones básicas
function navigate(page) {
  window.location.href = page;
}

function logout() {
  if (confirm("¿Estás seguro de que quieres cerrar sesión?")) {
    localStorage.removeItem("auth_token");
    localStorage.removeItem("user");
    window.location.href = "login.php";
  }
}

function updateUserInfo() {
  if (userData) {
    document.getElementById("userName").textContent = userData.nombre_completo;
    document.getElementById("userRole").textContent = userData.rol;
    document.getElementById("dropdownUserName").textContent =
      userData.nombre_completo;
  }
}

function updateCurrentTime() {
  const now = new Date();
  const options = {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  };
  document.getElementById("currentTime").textContent = now.toLocaleDateString(
    "es-ES",
    options
  );
}

async function verifyAuthentication() {
  authToken = localStorage.getItem("auth_token");
  const storedUser = localStorage.getItem("user");

  if (!authToken || !storedUser) {
    console.log("❌ No autenticado, redirigiendo al login...");
    window.location.href = "login.php";
    return false;
  }

  try {
    userData = JSON.parse(storedUser);
    console.log("✅ Usuario autenticado:", userData);

    // Verificar token con el servidor
    const formData = new FormData();
    formData.append("peticion", "verificar");
    formData.append("token", authToken);

    const response = await fetch("controllers/auth.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    if (!result.exito) {
      throw new Error("Token inválido");
    }

    return true;
  } catch (error) {
    console.error("❌ Error de autenticación:", error);
    localStorage.removeItem("auth_token");
    localStorage.removeItem("user");
    window.location.href = "login.php";
    return false;
  }
}
