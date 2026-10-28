<?php

it('probes admin/seo', function () {
    $this->seedRolesAndPermissions();
    $admin = createAdmin();
    $this->actingAs($admin);
    $this->withoutExceptionHandling();
    try {
        $res = $this->get('/admin/seo');
        dump('status', $res->status());
    } catch (Throwable $e) {
        dump(get_class($e), $e->getMessage(), $e->getFile().':'.$e->getLine());
        dump(substr($e->getTraceAsString(), 0, 600));
    }
    $this->assertTrue(true);
});
