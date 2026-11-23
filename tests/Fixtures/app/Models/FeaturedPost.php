<?php

namespace App\Models;

/**
 * Single-table inheritance: authorized by the parent's policy once that policy
 * is registered with Gate::policy().
 */
class FeaturedPost extends Post {}
