
document.addEventListener("DOMContentLoaded", function () {
        const notice = document.querySelector(".notice");

    if (notice) {
        setTimeout(function () {
            notice.style.transition = "opacity 0.5s ease";
            notice.style.opacity = "0";

            setTimeout(function () {
                notice.style.display = "none";
            }, 500);
        }, 3000);
    }
    const imageInput = document.getElementById("profile_image");
    const imagePreview = document.getElementById("profilePreview");
    const profileInitial = document.getElementById("profileInitial");
    const profileAvatar = document.getElementById("profileAvatar");

    if (!imageInput || !imagePreview || !profileAvatar) {
        return;
    }

    let previewUrl = null;

    // فتح اختيار الصورة عند الضغط على الدائرة
    function openImagePicker() {
        imageInput.click();
    }

    profileAvatar.addEventListener("click", openImagePicker);

    // دعم فتح الاختيار باستخدام لوحة المفاتيح
    profileAvatar.addEventListener("keydown", function (event) {
        if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            openImagePicker();
        }
    });

    // عرض معاينة الصورة الجديدة
    imageInput.addEventListener("change", function () {
        const file = this.files && this.files[0];

        if (!file) {
            return;
        }

        const allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        if (!allowedTypes.includes(file.type)) {
            alert("يرجى اختيار صورة بصيغة JPG أو PNG أو WEBP.");
            this.value = "";
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            alert("حجم الصورة يجب ألا يتجاوز 2 ميجابايت.");
            this.value = "";
            return;
        }

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }

        previewUrl = URL.createObjectURL(file);

        imagePreview.src = previewUrl;
        imagePreview.hidden = false;

        if (profileInitial) {
            profileInitial.hidden = true;
        }
    });
});
