<style>
.loading-overlay { position: fixed; inset: 0; background: rgba(255,255,255,0.6); backdrop-filter: blur(2px); display: none; align-items: center; justify-content: center; z-index: 9999; }
.loading-overlay .loading-spinner {
  width: {{ $size ?? 36 }}px;
  height: {{ $size ?? 36 }}px;
  border-radius: 50%;
  position: relative;
  background: conic-gradient({{ $color ?? '#f87171' }} 0 180deg, transparent 180deg 360deg);
  -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 3px), #000 0);
  mask: radial-gradient(farthest-side, transparent calc(100% - 3px), #000 0);
  animation: spin 0.7s linear infinite;
}
.loading-overlay .loading-spinner::after {
  content: "";
  position: absolute;
  inset: 50% auto auto 50%;
  transform: translate(-50%, -50%);
  width: {{ $inner ?? 14 }}px;
  height: {{ $inner ?? 14 }}px;
  border-radius: 50%;
  background: {{ $color ?? '#f87171' }};
  box-shadow: 0 0 0 4px #fde2e2;
}
.loading-overlay .loading-text { margin-top: .6rem; color: {{ $textColor ?? '#f87171' }}; font-weight: 400; font-size: {{ $textSize ?? 20 }}px; text-shadow: none; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<div id="{{ $id ?? 'loadingOverlay' }}" class="loading-overlay" aria-hidden="true">
    <div class="inner" style="display:flex;flex-direction:column;align-items:center;justify-content:center;">
        <div class="loading-spinner"></div>
        <div class="loading-text">{{ $label ?? 'Cargando datos...' }}</div>
    </div>
</div>