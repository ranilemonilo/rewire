<?php

namespace App\Concerns;

trait AuthorizesContentManagement
{
    /**
     * Ensure the current user is allowed to create, update, or delete CMS content
     * (Page, Service, GalleryItem, Post). Route middleware (role:admin on the
     * content-management route group) already blocks non-admins from reaching these
     * components, but this check exists as defense-in-depth at the action level, in
     * case that middleware is ever loosened or a component is reused elsewhere.
     */
    protected function authorizeContentManagement(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
    }
}