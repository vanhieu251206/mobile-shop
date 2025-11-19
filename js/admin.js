// Key lưu data
const KEY = "MOBILE_SHOP_PRODUCTS";

// Lấy data: ưu tiên localStorage, fallback sang window.PRODUCTS
function loadProducts() {
  try {
    const raw = localStorage.getItem(KEY);
    if (raw) return JSON.parse(raw);
  } catch {}
  return (window.PRODUCTS || []).map(p => ({ stock: 0, ...p, img: p.img || p.imgMain, imgMain: p.imgMain || p.img }));
}

// Lưu
function saveProducts(list) {
  localStorage.setItem(KEY, JSON.stringify(list));
}

let products = loadProducts();
let editingId = null;

// Format tiền
const money = v => (v||0).toLocaleString("vi-VN");

// Render bảng
function renderTable(filter="") {
  const tb = document.getElementById("tbody");
  const f = filter.trim().toLowerCase();
  const rows = products
    .filter(p => !f || (p.name?.toLowerCase().includes(f) || p.brand?.toLowerCase().includes(f)))
    .map(p => `
      <tr>
        <td>${p.id}</td>
        <td>${p.name || ""}</td>
        <td>${p.brand || ""}</td>
        <td class="text-right">${money(p.price)}</td>
        <td class="text-center">${p.stock ?? 0}</td>
        <td>${p.imgMain ? `<img src="${p.imgMain}" style="width:55px;height:55px;object-fit:cover;border-radius:6px">` : ""}</td>
        <td>
          <button class="btn btn-sm btn-outline-primary" onclick="edit(${p.id})"><i class="fa fa-edit"></i></button>
          <button class="btn btn-sm btn-outline-danger ml-2" onclick="removeP(${p.id})"><i class="fa fa-trash"></i></button>
        </td>
      </tr>
    `).join("");
  tb.innerHTML = rows || `<tr><td colspan="7" class="text-center text-muted">Chưa có sản phẩm</td></tr>`;
}
renderTable();

// Fill form khi sửa
function fillForm(p) {
  const $ = id => document.getElementById(id);
  $("#id").value = p.id ?? "";
  $("#name").value = p.name ?? "";
  $("#brand").value = p.brand ?? "";
  $("#price").value = p.price ?? "";
  $("#stock").value = p.stock ?? 0;
  $("#chip").value = p.chip ?? "";
  $("#ram").value = p.ram ?? "";
  $("#storage").value = p.storage ?? "";
  $("#screen").value = p.screen ?? "";
  $("#battery").value = p.battery ?? "";
  $("#camera").value = p.camera ?? "";
  $("#imgMain").value = p.imgMain ?? p.img ?? "";
  $("#desc").value = p.desc ?? "";
  $("#gallery").value = (p.gallery || []).join(", ");
}

// Sửa
window.edit = function(id){
  const p = products.find(x => x.id === id);
  if (!p) return;
  editingId = id;
  document.getElementById("form-title").innerText = "Sửa sản phẩm #" + id;
  fillForm(p);
  window.scrollTo({ top: 0, behavior: "smooth" });
};

// Xóa
window.removeP = function(id){
  if (!confirm("Xóa sản phẩm #" + id + " ?")) return;
  products = products.filter(x => x.id !== id);
  saveProducts(products);
  renderTable(document.getElementById("search").value);
};

// Reset form
document.getElementById("btn-reset").onclick = () => {
  editingId = null;
  document.getElementById("form-title").innerText = "Thêm sản phẩm";
  document.getElementById("product-form").reset();
  document.getElementById("id").value = "";
};

// Submit (Thêm/Sửa)
document.getElementById("product-form").onsubmit = (e) => {
  e.preventDefault();
  const $ = id => document.getElementById(id);
  const data = {
    id: $("#id").value ? Number($("#id").value) : undefined,
    name: $("#name").value.trim(),
    brand: $("#brand").value.trim(),
    price: Number($("#price").value || 0),
    stock: Number($("#stock").value || 0),
    chip: $("#chip").value.trim(),
    ram: $("#ram").value.trim(),
    storage: $("#storage").value.trim(),
    screen: $("#screen").value.trim(),
    battery: $("#battery").value.trim(),
    camera: $("#camera").value.trim(),
    imgMain: $("#imgMain").value.trim(),
    img: $("#imgMain").value.trim(), // để shop.html cũ vẫn chạy
    desc: $("#desc").value.trim(),
    gallery: $("#gallery").value.split(",").map(s => s.trim()).filter(Boolean),
    createdAt: (new Date()).toISOString().slice(0,10)
  };

  if (!data.name || !data.brand) {
    alert("Vui lòng nhập Tên và Hãng");
    return;
  }

  if (editingId) {
    // update
    const i = products.findIndex(x => x.id === editingId);
    if (i >= 0) {
      data.id = editingId;
      products[i] = { ...products[i], ...data };
    }
  } else {
    // add
    const maxId = products.reduce((m, x) => Math.max(m, x.id || 0), 0);
    data.id = data.id || (maxId + 1);
    products.push(data);
  }

  saveProducts(products);
  renderTable(document.getElementById("search").value);
  document.getElementById("btn-reset").click();
};

// Tìm kiếm
document.getElementById("search").oninput = (e) => {
  renderTable(e.target.value);
};
