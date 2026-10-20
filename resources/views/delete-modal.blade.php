<div class="modal fade deleteModal" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <span id="{{ $id }}Label">Delete the schedule?</span>
                <button type="button" data-bs-dismiss="modal" class="btn-close" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" style="color: var(--cx-muted); font-size: 14px">{{ $text ?? 'The schedule is removed, the runs of the command stay.' }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" data-bs-dismiss="modal" class="cx-btn ghost">Cancel</button>
                <form method="POST" action="{{ $action }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="cx-btn danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
