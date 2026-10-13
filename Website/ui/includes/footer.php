
  </main>
</div>
<div class="fixed bottom-5 right-5 z-[60] flex max-w-sm flex-col gap-2" id="toast-region" aria-live="polite"></div>
<dialog class="w-[min(92vw,460px)] rounded-xl p-0 shadow-xl backdrop:bg-inverse-surface/50" id="confirm-dialog">
  <form method="dialog" class="flex flex-col gap-space-md p-space-lg">
    <h2 class="font-headline-md text-headline-md" id="confirm-title">Xác nhận thao tác</h2>
    <p class="text-on-surface-variant" id="confirm-message"></p>
    <div class="flex justify-end gap-space-sm">
      <button class="rounded-lg bg-surface-container px-space-md py-2 font-label-md" value="cancel" type="submit">Hủy</button>
      <button class="rounded-lg bg-primary-container px-space-md py-2 font-label-md text-on-primary" value="ok" id="confirm-ok" type="submit">Xác nhận</button>
    </div>
  </form>
</dialog>
<script src="assets/js/common.js"></script>
</body>
</html>
