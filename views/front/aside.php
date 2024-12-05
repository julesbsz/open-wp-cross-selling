<?php

function owcs_render_aside() {
    if (!is_product()) return;

    ?>

    <aside id="owcs-aside" class="owcs-aside">
        <p>Je suis un super drawer</p>
    </aside>

    <?php
}

add_action('wp_footer', 'owcs_render_aside');