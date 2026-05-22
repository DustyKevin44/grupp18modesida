document.addEventListener("click", async function (e) {
  if (!e.target.matches(".delete-btn")) return;
  const btn = e.target;
  const postId = btn.getAttribute("data-post-id");
  if (!postId) return;
  if (
    !confirm(
      "Are you sure you want to delete this post? This cannot be undone.",
    )
  )
    return;

  btn.disabled = true;
  try {
    const form = new FormData();
    form.append("post_id", postId);

    const res = await fetch("delete_post.php", {
      method: "POST",
      body: form,
    });
    const data = await res.json();
    if (data && data.success) {
      const card = btn.closest(".post-card");
      if (card) card.remove();
    } else {
      alert("Delete failed: " + (data.message || "unknown error"));
      btn.disabled = false;
    }
  } catch (err) {
    console.error(err);
    alert("Network error while deleting post");
    btn.disabled = false;
  }
});
