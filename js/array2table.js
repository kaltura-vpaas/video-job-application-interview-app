function renderTableHeader(data) {
  const header = Object.keys(data[0]);
  const head = header.map((key, index) => `<th key="${index}">${key}</th>`);
  return head.join('');
}

function renderTableRows(data) {
  const keys = Object.keys(data[0]);
  const table = data.map((row, index) => {
    return (
      `<tr key=${index}>
        ${keys.map((key, index) => (
          `<td key=${index}>${row[key]}</td>`
        )).join('')}
      </tr>`
    );
  });
  return table.join('');
}
