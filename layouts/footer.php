<!-- layouts/footer.php -->
<footer class="nvj-footer">
    <div class="footer-content container">
        <div class="footer-left">
            <iframe 
                src="https://www.google.com/maps?q=Nepal+Van+Java,+Kaliangkrik,+Magelang,+Jawa+Tengah&output=embed" 
                width="100%" 
                height="250" 
                style="border:0; border-radius:12px;" 
                allowfullscreen 
                loading="lazy">
            </iframe>
        </div>

        <div class="footer-right">
            <h5 class="fw-bold mb-3">Nepal Van Java</h5>
            <p><i class="fa-solid fa-location-dot me-2"></i> Nepal Van Java, Dusun, Butuh, Temanggung, Kec. Kaliangkrik, Kabupaten Magelang, Jawa Tengah 56153</p>
            <p><i class="fa-solid fa-phone me-2"></i> +62 8xx-xxxx-xxxx</p>
            <!-- <p><i class="fa-solid fa-envelope me-2"></i> info@nepal-vanjava.com</p> -->
        </div>
    </div>

    <div class="footer-bottom text-center">
        <p class="m-0">&copy; 2025 <strong>Nepal Van Java</strong>. All rights reserved.</p>
    </div>
</footer>

<style>
/* === FOOTER STYLING === */
.nvj-footer {
    background-color: #0E7A3C;
    color: #fff;
    font-family: 'Poppins', sans-serif;
    padding-top: 40px;
}

.footer-content {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 30px;
    padding-bottom: 30px;
}

.footer-left, .footer-right {
    flex: 1 1 45%;
    min-width: 280px;
}

.footer-right p {
    margin: 8px 0;
    font-size: 15px;
}

.footer-bottom {
    background-color: #0c6a33;
    padding: 15px 10px;
    font-size: 14px;
}

.footer-right h5 {
    font-size: 20px;
}

/* Responsif untuk mobile */
@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .footer-right p {
        justify-content: center;
    }

    .footer-left iframe {
        height: 200px;
    }
}
</style>
