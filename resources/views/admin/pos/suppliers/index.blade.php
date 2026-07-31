<div class="col-xl-12"><div class="page-title"><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSupplier">+ New Supplier</button></div></div>
<div class="col-xl-12"><div class="card"><div class="card-body"><div class="table-responsive">
    <table class="table" id="myTable"><thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Address</th><th>Status</th><th>Action</th></tr></thead><tbody>
    @foreach ($suppliers as $supplier)
        <tr><td>{{ $supplier->name }}</td><td>{{ $supplier->phone }}</td><td>{{ $supplier->email }}</td><td>{{ $supplier->address }}</td><td>{{ $supplier->is_active ? 'Active' : 'Inactive' }}</td><td><button class="btn btn-warning btn-xs" data-bs-toggle="modal" data-bs-target="#editSupplier{{ $supplier->id }}">Edit</button></td></tr>
    @endforeach
    </tbody></table>
</div></div></div></div>
<div class="modal fade" id="addSupplier"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" action="{{ route('pos-suppliers.store') }}" method="POST">@csrf<div class="modal-header"><h5>New Supplier</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">@include('admin.pos.suppliers.partials.form', ['supplier' => null])</div><div class="modal-footer"><button class="btn btn-primary">Save</button></div></form></div></div>
@foreach ($suppliers as $supplier)
<div class="modal fade" id="editSupplier{{ $supplier->id }}"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" action="{{ route('pos-suppliers.update', $supplier->id) }}" method="POST">@csrf @method('PUT')<div class="modal-header"><h5>Edit Supplier</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">@include('admin.pos.suppliers.partials.form', ['supplier' => $supplier])</div><div class="modal-footer"><button class="btn btn-primary">Update</button></div></form></div></div>
@endforeach
