function attachCompanySuggest(inputId){
  const input = document.getElementById(inputId);
  if(!input) return;

  // Create suggestion box wrapper
  let box = document.createElement('div');
  box.className = 'suggest-box';
  box.style.position = 'relative';
  
  // Wrap input
  input.parentElement.insertBefore(box, input);
  box.appendChild(input);

  // Create Dropdown List
  let list = document.createElement('div');
  list.className = 'suggest-list';
  Object.assign(list.style, {
      position: 'absolute',
      left: '0', right: '0', top: '100%',
      zIndex: '1000',
      background: '#ffffff',        // Light Theme BG
      border: '2px solid #2c3e50',  // Ink Border
      borderRadius: '0 0 6px 6px',
      boxShadow: '4px 4px 0px rgba(44, 62, 80, 0.15)',
      maxHeight: '200px',
      overflowY: 'auto',
      display: 'none',
      marginTop: '-2px'
  });
  box.appendChild(list);

  let lastQ = null;
  let tmr = null;

  function hide(){ 
      setTimeout(() => {
          list.style.display = 'none'; 
          list.innerHTML = ''; 
      }, 200); // Delay to allow click event
  }

  function show(items){
    list.innerHTML = '';
    if(!items || !items.length){
        // Optional: Show "No results" or nothing
        list.style.display = 'none';
        return; 
    }
    
    items.forEach(name => {
      const row = document.createElement('div');
      row.textContent = name;
      Object.assign(row.style, {
          padding: '10px 12px',
          cursor: 'pointer',
          color: '#2c3e50',
          fontWeight: '600',
          borderBottom: '1px dashed #eee'
      });

      row.onmouseenter = () => {
          row.style.background = '#00bcd4'; // Pop Cyan
          row.style.color = '#ffffff';
      };
      row.onmouseleave = () => {
          row.style.background = 'transparent';
          row.style.color = '#2c3e50';
      };
      row.onmousedown = (e) => {
          e.preventDefault(); // Prevent blur
          input.value = name;
          list.style.display = 'none';
          // Trigger machine fetch if function exists (in service_new/edit)
          if(typeof fetchMachines === 'function') fetchMachines();
      };
      list.appendChild(row);
    });
    list.style.display = 'block';
  }

  async function doSearch() {
      const q = input.value.trim();
      try {
        const res = await fetch('/api/company_suggest.php?q=' + encodeURIComponent(q));
        const data = await res.json();
        show(data);
      } catch(e) { list.style.display = 'none'; }
  }

  // Event: Typing
  input.addEventListener('input', () => {
      if(tmr) clearTimeout(tmr);
      tmr = setTimeout(doSearch, 150);
  });

  // Event: Click/Focus (Dropdown behavior)
  input.addEventListener('focus', doSearch);
  input.addEventListener('click', doSearch); // Allow clicking again to reopen

  // Event: Blur
  input.addEventListener('blur', hide);
}