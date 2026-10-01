<?php
$inIframeMode = isset($inIframe) && $inIframe;

if (!$inIframeMode) {
    echo '</div>';
}
echo '</body></html>';
