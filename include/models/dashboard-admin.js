document.addEventListener("DOMContentLoaded", () => {
  const lockButtons = document.querySelectorAll(".btn-lock-toggle");

  lockButtons.forEach((button) => {
    button.addEventListener("click", async (e) => {
      e.preventDefault();

      const userId = button.dataset.userId;
      const isCurrentlyLocked = button.dataset.locked === "1";

      button.classList.add("loading");
      button.disabled = true;

      try {
        const response = await fetch("dashboard.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: `action=toggle_lock&user_id=${userId}`,
        });

        const result = await response.json();

        if (result.success) {
          const newLocked = result.locked;
          const row = button.closest(".user-row");
          const statusCell = row.querySelector(".status-cell .status-badge");

          button.dataset.locked = newLocked ? "1" : "0";
          button.textContent = newLocked ? "Unlock" : "Lock";

          statusCell.classList.remove("active", "locked");
          statusCell.classList.add(newLocked ? "locked" : "active");
          statusCell.textContent = newLocked ? "Locked" : "Active";
        } else {
          alert("Error: " + (result.error || "Unknown error"));
        }
      } catch (error) {
        console.error("Error:", error);
        alert("Failed to update user status");
      } finally {
        button.classList.remove("loading");
        button.disabled = false;
      }
    });
  });
});
