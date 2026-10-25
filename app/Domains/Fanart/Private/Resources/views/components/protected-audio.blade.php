{{-- Friction only: no native download entry, no context menu, no drag. Inline handlers on purpose: viewer and profile groups are lazy fragments. --}}
<audio controls controlsList="nodownload noplaybackrate" oncontextmenu="return false" ondragstart="return false" {{ $attributes }}></audio>
