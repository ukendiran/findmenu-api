<?php

it('responds to /api/v1/health', function () {
    $res = $this->get('/api/v1/health');
    $res->assertOk()->assertJsonStructure(['ok', 'service', 'version', 'time']);
});
