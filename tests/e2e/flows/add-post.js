

const addPostFlow = async (page, html, issueSlug, issueCount ) => {

  await page.goto('/wp-admin/post.php');
  await page.locator('#wpbody-content').getByRole('link', { name: 'Add New' }).click();

  await page.getByLabel('Add title').click();
  await page.getByLabel('Add title').fill('A new post');
  await page.getByLabel('Toggle block inserter').click();
  await page.getByPlaceholder('Search').fill('ht');
  await page.getByRole('option', { name: 'Custom HTML' }).click();
  await page.getByPlaceholder('Write HTML…').fill(html);


  //Check details before publish.
  await page.getByRole('button', { name: 'Details' }).click();
  const detailItem0 = page.locator('div').filter({ hasText: new RegExp('0' + ' ' + issueSlug + '$') });
  const detailCountItem0 = detailItem0.locator('.edac-details-rule-count');
  const detailCount0 = await detailCountItem0.innerText();


  //Check summary before publish.
  await page.getByRole('button', { name: 'Summary' }).click();
  const summaryItem0 = await page.getByText('0 Errors');
  const summaryCountItem0 = summaryItem0.locator('.edac-panel-number');
  const summaryCount0 = await summaryCountItem0.innerText();

  // Publish.
  await page.getByRole('button', { name: 'Publish', exact: true }).click();
  await page.getByLabel('Editor publish').getByRole('button', { name: 'Publish', exact: true }).click();

  //Check details after publish.
  await page.getByRole('button', { name: 'Details' }).click();
  const detailItem1 = page.locator('div').filter({ hasText: new RegExp(issueCount + ' ' + issueSlug + '$') });
  const detailCountItem1 = detailItem1.locator('.edac-details-rule-count');
  const detailCount1 = await detailCountItem1.innerText();


  //Check summary after publish.
  await page.getByRole('button', { name: 'Summary' }).click();
  const summaryItem1 = await page.getByText(issueCount + ' Error');

  const summaryCountItem1 = summaryItem1.locator('.edac-panel-number');
  const summaryCount1 = await summaryCountItem1.innerText();

  return (
    parseInt(detailCount0) == 0 && parseInt(summaryCount0) == 0 &&
    parseInt(detailCount1) == issueCount && parseInt(summaryCount1) == issueCount
  );

};


export default addPostFlow;
