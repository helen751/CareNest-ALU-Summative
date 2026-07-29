<?php
  $resetParameters = [
      'uid' => $_GET['uid'] ?? '',
      'scope' => $_GET['scope'] ?? '',
      'nonce' => $_GET['nonce'] ?? '',
      'token' => $_GET['token'] ?? ''
  ];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password - CareNest</title>
  <link rel="icon" type="image/png" href="assets/images/logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <main class="container py-5 flex-grow-1 d-flex align-items-center">
    <div class="auth-card w-100 my-4">
      <div class="text-center mb-4">
        <div class="brand-logo-frame mx-auto mb-3" style="width: 56px; height: 56px; border-radius: 16px;">
          <i class="fa-solid fa-lock" style="font-size: 1.5rem;"></i>
        </div>
        <h3 class="fw-bold text-dark mb-1">Create New Password</h3>
        <p class="text-muted small mb-0">Choose a secure password for your CareNest account.</p>
      </div>

      <form id="resetPasswordForm" style="display: none;">
        <div class="mb-3">
          <label for="newPassword" class="form-label fw-semibold small text-muted">New Password</label>
          <input type="password" class="form-control py-2" id="newPassword" name="password" minlength="6" autocomplete="new-password" required
          >
        </div>
        <div class="mb-4">
          <label for="confirmPassword" class="form-label fw-semibold small text-muted">Confirm Password</label>
          <input
            type="password"
            class="form-control py-2"
            id="confirmPassword"
            name="confirm_password"
            minlength="6"
            autocomplete="new-password"
            required
          >
        </div>
        <button type="submit" class="btn btn-primary-custom w-100 py-2">
          <i class="fa-solid fa-shield-halved me-2"></i> Update Password
        </button>
      </form>

      <div class="text-center mt-4">
        <a href="login" class="fw-semibold text-decoration-none" style="color: var(--primary);">
          <i class="fa-solid fa-arrow-left me-1"></i> Back to Login
        </a>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    const resetParameters = <?php echo json_encode(
        $resetParameters,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ); ?>;
    const resetForm = document.getElementById("resetPasswordForm");

    function buildResetFormData(includePasswords = false) {
      const formData = includePasswords
        ? new FormData(resetForm)
        : new FormData();

      Object.entries(resetParameters).forEach(([key, value]) => {
        formData.append(key, value);
      });

      return formData;
    }

    async function verifyResetLink() {
      Swal.fire({
        title: "Verifying Reset Link...",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      });

      try {
        const response = await fetch("process.php?action=validate_password_reset", {
          method: "POST",
          body: buildResetFormData()
        });
        const result = await response.json();

        if (!result.success) {
          await Swal.fire({
            icon: "error",
            title: "Invalid Reset Link",
            text: result.message,
            confirmButtonColor: "#0F5C5E"
          });
          return;
        }

        Swal.close();
        resetForm.style.display = "block";
      } catch (error) {
        Swal.fire({
          icon: "error",
          title: "Connection Error",
          text: "The reset link could not be verified.",
          confirmButtonColor: "#0F5C5E"
        });
      }
    }

    resetForm.addEventListener("submit", async event => {
      event.preventDefault();

      const password = document.getElementById("newPassword").value;
      const confirmation = document.getElementById("confirmPassword").value;
      if (password !== confirmation) {
        Swal.fire({
          icon: "warning",
          title: "Passwords Do Not Match",
          text: "Enter the same password in both fields.",
          confirmButtonColor: "#0F5C5E"
        });
        return;
      }

      Swal.fire({
        title: "Updating Password...",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      });

      try {
        const response = await fetch("process.php?action=reset_password", {
          method: "POST",
          body: buildResetFormData(true)
        });
        const result = await response.json();

        await Swal.fire({
          icon: result.success ? "success" : "error",
          title: result.success ? "Password Updated" : "Unable to Reset",
          text: result.message,
          confirmButtonColor: "#0F5C5E"
        });

        if (result.success) {
          window.location.href = "login";
        }
      } catch (error) {
        Swal.fire({
          icon: "error",
          title: "Connection Error",
          text: "The password could not be updated. Please try again.",
          confirmButtonColor: "#0F5C5E"
        });
      }
    });

    verifyResetLink();
  </script>
</body>
</html>
