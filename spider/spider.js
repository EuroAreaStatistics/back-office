console.time("time");
const fs = require("fs");
const path = require("path");
const puppeteer = require("puppeteer");
const { execFileSync } = require("child_process");

const DEBUG = false;

if (process.argv.length !== 3) {
  throw new Error(
    `usage: node ${path.basename(process.argv[1])} <output-directory>`
  );
}

const outputDir = process.argv[2];
fs.accessSync(outputDir, fs.constants.R_OK | fs.constants.W_OK);
const shotDir = `${outputDir}/shots`;
fs.mkdirSync(shotDir);
const downloadDir = `${outputDir}/files`;
fs.mkdirSync(downloadDir);

// partition arr into arrays of n elements, last array may contain less then n elements
const partition = (arr, n) =>
  arr
    .reduce(
      (acc, x) =>
        acc.length && acc[0].length < n
          ? (acc[0].push(x), acc)
          : [[x]].concat(acc),
      []
    )
    .reverse();

const blacklist = [
  /\/three\.min\.js$/, // 3d bank notes take 6+ minutes to load and render
];

const whitelist = [
  "https://www.euro-area-statistics.org/",
  "https://fonts.googleapis.com/",
  "https://fonts.gstatic.com/",
  // "https://utils.euro-area-statistics.org/",
];

(async () => {
  const browser = await puppeteer.launch({
    args: ["--disable-dev-shm-usage", "--no-sandbox"],
    executablePath: "google-chrome-stable",
    dumpio: DEBUG,
  });
  const page = await browser.newPage();
  // workaround puppeteer issue #7475
  await page.setCacheEnabled(false);
  // remove Headless from user agent
  const userAgent = (await browser.userAgent()).replace("Headless", "");
  console.log("user agent:", userAgent);
  await page.setUserAgent(userAgent);
  if (DEBUG) page.on("*", (type) => console.log("page event", type));
  let n = 0;

  const newFile = (suffix) => {
    const file = `${shotDir}/${n.toString().padStart(3, "0")}.${suffix}`;
    n++;
    console.log("writing", file);
    return file;
  };

  const shoot = async () => {
    const path = newFile("png");
    await page.screenshot({ path, fullPage: true });
    execFileSync("exiftool", [
      "-overwrite_original",
      `-Description=${page.url()}`,
      path,
    ]);
  };

  const dump = async () => {
    const html = await page.content();
    fs.writeFileSync(newFile("html"), html);
  };

  const finishAnimations = async () => {
    await page.evaluate(() =>
      document
        .getAnimations()
        .forEach(
          (animation) =>
            animation.effect.getTiming().iterations !== Infinity &&
            animation.finish()
        )
    );
    for (const frame of page.mainFrame().childFrames()) {
      if (frame.isDetached()) continue;
      await frame.evaluate(() =>
        document
          .getAnimations()
          .forEach(
            (animation) =>
              animation.effect.getTiming().iterations !== Infinity &&
              animation.finish()
          )
      );
    }
  };

  const waitForDownload = async (actionPromise) => {
    const client = await page.target().createCDPSession();
    await client.send("Browser.setDownloadBehavior", {
      behavior: "allowAndName",
      eventsEnabled: true,
      downloadPath: path.resolve(downloadDir),
    });
    client.on("Browser.downloadWillBegin", ({ url, guid }) =>
      console.log(`downloading ${url} to ${downloadDir}/${guid}`)
    );
    const results = await Promise.all([
      new Promise((resolve) =>
        client.on("Browser.downloadProgress", ({ state }) => {
          if (state !== "inProgress") resolve(state);
        })
      ),
      actionPromise(),
    ]);
    await client.detach();
    return results[0];
  };

  const downloadFiles = async (count) => {
    const downloads = await page.$$("a.download");
    if (downloads.length) {
      for (download of downloads.slice(0, count)) {
        const result = await waitForDownload(() => download.click());
        console.log("download", result);
      }
    } else {
      await page.click("#templateSelect2");
      // trends template has data-code=1
      await Promise.all([
        page.waitForNavigation({ waitUntil: "networkidle0" }),
        page.click('#templateSelect2 > span[data-code="1"]'),
      ]);
      await downloadFiles(count);
      await page.goBack({ waitUntil: "networkidle0" });
      await finishAnimations();
    }
  };

  const viewPort = {
    width: 1620,
    height: 1080,
  };
  await page.setViewport(viewPort);
  page.on("console", (msg) => {
    const t = msg.text();
    if (t !== "Failed to load resource: net::ERR_FAILED")
      console.log("PAGE LOG:", t);
  });
  page.on("pageerror", ({ message }) => console.log(message));
  page.on("requestfailed", (req) =>
    console.log(req.failure().errorText, req.url())
  );
  await page.setRequestInterception(true);
  page.on("request", (req) => {
    if (req.url().startsWith("data:")) {
      req.continue();
      return;
    }
    if (req.isNavigationRequest()) console.log("");
    if (
      !blacklist.some((url) => url.test(req.url())) &&
      whitelist.some((url) => req.url().startsWith(url))
    ) {
      console.log(req.method(), req.url());
      req.continue();
    } else {
      console.log(req.method() + "*", req.url());
      req.respond({
        contentType: "text/plain",
        body: "",
      });
    }
  });

  // resize height to take screen shot of full calculator
  const shootCalculator = async () => {
    let heightDiff = 0;
    for (const frame of page.mainFrame().childFrames()) {
      const [frameCH, frameSH] = await frame.evaluate(() => [
        document.scrollingElement.clientHeight,
        document.scrollingElement.scrollHeight,
      ]);
      heightDiff = Math.max(heightDiff, frameSH - frameCH);
    }
    if (heightDiff) {
      await page.setViewport({
        ...viewPort,
        height: viewPort.height + heightDiff,
      });
      await finishAnimations();
    }
    await shoot();
    if (heightDiff) {
      await page.setViewport(viewPort);
      await finishAnimations();
    }
  };

  const visitPublications = async (homeURL) => {
    if (page.url() !== homeURL) {
      await page.goto(homeURL, { waitUntil: "networkidle0" });
      await finishAnimations();
    }
    const pubs = await page.$$eval("tr", (els) =>
      els.map((el) => el.textContent.trim())
    );
    for (const [i, pub] of pubs.entries()) {
      if (pub === "") continue;
      console.log("publication:", pub);
      if (page.url() !== homeURL) {
        await page.goto(homeURL, { waitUntil: "networkidle0" });
        await finishAnimations();
      }
      await Promise.all([
        page.waitForNavigation({ waitUntil: "networkidle0" }),
        page.click(`tr:nth-child(${i + 1}) img`),
      ]);
      await finishAnimations();
      await shoot();
      await Promise.all([
        page.waitForNavigation({ waitUntil: "networkidle0" }),
        page.click(".button"),
      ]);
      await finishAnimations();

      const visited = [];
      const pubURL = page.url();
      const chapters = await page.$$eval("span.chapterTitle", (els) =>
        els.map((el) => el.textContent.trim())
      );
      for (const [k, title] of chapters.entries()) {
        console.log("chapter:", title);
        if (page.url() !== pubURL) {
          await page.goto(pubURL, { waitUntil: "networkidle0" });
          await finishAnimations();
        }
        const buttons = await page.$$("span.chapterTitle");
        console.time("waited");
        await Promise.all([
          page.waitForNavigation({
            waitUntil: "networkidle0",
            timeout: 2 * 60 * 1000,
          }),
          buttons[k].click(),
        ]);
        console.timeEnd("waited");
        await finishAnimations();
        if (visited.indexOf(page.url()) !== -1) continue;
        visited.push(page.url());
        if (/inflation\/bloc-4a/.test(page.url())) {
          await shootCalculator();
        } else {
          await shoot();
        }
        const chapterURL = page.url();
        const sections = await page.$$eval(".button", (els) =>
          els.map((el) =>
            el.parentNode.parentNode.querySelector("h3").textContent.trim()
          )
        );
        for (const [j, title] of sections.entries()) {
          console.log("section:", title);
          if (page.url() !== chapterURL) {
            await page.goto(chapterURL, { waitUntil: "networkidle0" });
            await finishAnimations();
          }
          const buttons = await page.$$(".button");
          console.time("waited");
          await Promise.all([
            page.waitForNavigation({
              waitUntil: "networkidle0",
              timeout: 2 * 60 * 1000,
            }),
            buttons[j].click(),
          ]);
          console.timeEnd("waited");
          await finishAnimations();
          if (visited.indexOf(page.url()) !== -1) continue;
          visited.push(page.url());
          await shoot();
        }
      }
    }
  };

  const visitProjects = async (homeURL) => {
    // keep track of indices since page is reloaded on every iteration
    // nth-child() is 1-based
    const blocks = (await page.$$("div > ul.indicators")).keys();
    for (const b of blocks) {
      const groups = await page.$$eval(
        `div:nth-child(${b + 1}) > ul.indicators > li > a`,
        (els) => els.map((el) => el.textContent)
      );
      for (const [g, title] of groups.entries()) {
        const projects = await page.$$eval(
          `div:nth-child(${b + 1}) > ul.indicators > li:nth-child(${
            g + 1
          }) > a + ul > li > a`,
          (els) => els.map((el) => el.textContent)
        );
        for (const [p, title] of projects.entries()) {
          await page.click(
            `div:nth-child(${b + 1}) > ul.indicators > li:nth-child(${
              g + 1
            }) > a`
          );
          await finishAnimations();
          if (!p) {
            await shoot();
          }
          await Promise.all([
            page.waitForNavigation({ waitUntil: "networkidle0" }),
            page.click(
              `div:nth-child(${b + 1}) > ul.indicators > li:nth-child(${
                g + 1
              }) > a + ul > li:nth-child(${p + 1}) > a`
            ),
          ]);
          const flows = (await page.$$("div.flow-viz-player")).length;
          if (flows) {
            await finishAnimations();
            await shoot();
            await downloadFiles();
            const tabs = await page.$$eval("#tabSelect > span", (els) =>
              els.map((el) => [el.textContent, el.dataset.tab])
            );
            for (const [t, [title, id]] of tabs.entries()) {
              if (!t) continue;
              await page.click("#tabSelect");
              await finishAnimations();
              await Promise.all([
                page.waitForNavigation({ waitUntil: "networkidle0" }),
                page.click(`#tabSelect > span[data-tab="${id}"]`),
              ]);
              await finishAnimations();
              await shoot();
              await downloadFiles();
            }
          } else {
            await finishAnimations();
            const tabs = await page.$$eval("ul.tabs > li", (els) =>
              els.map((el) => el.textContent)
            );
            for (const [t, title] of tabs.entries()) {
              if (t) {
                await Promise.all([
                  page.waitForNavigation({ waitUntil: "networkidle0" }),
                  page.click(`ul.tabs > li:nth-child(${t + 1})`),
                ]);
                await finishAnimations();
              }
              await shoot();
              await downloadFiles();
              // fetch inactive indicators grouped by drop down
              const dropDowns = await page.$$eval(
                ".tabcontent div.dropdown",
                (els) =>
                  els.map((el) =>
                    Array.from(el.querySelectorAll("span[data-code]")).map(
                      (el) => [el.dataset.code, el.title]
                    )
                  )
              );
              // only use indicators listed in all drop downs (others are already visible)
              const indicators = partition(
                dropDowns[0].filter(([code]) =>
                  dropDowns
                    .slice(1)
                    .every((d) => d.map(([c]) => c).indexOf(code) !== -1)
                ),
                dropDowns.length
              );
              for (const group of indicators) {
                for (const [g, [code, title]] of group.entries()) {
                  const drops = await page.$$(".tabcontent div.dropdown");
                  await drops[g].click();
                  await finishAnimations();
                  const button = await drops[g].$(`span[data-code="${code}"]`);
                  await Promise.all([
                    page.waitForNavigation({ waitUntil: "networkidle0" }),
                    button.click(),
                  ]);
                  await finishAnimations();
                }
                await shoot();
                // only download changed indicators
                // assume download links are in same document order as dropdowns
                await downloadFiles(group.length);
              }
            }
          }
          await page.goto(homeURL, { waitUntil: "networkidle0" });
          await finishAnimations();
        }
      }
    }
  };

  try {
    await page.goto("https://www.euro-area-statistics.org/", {
      waitUntil: "networkidle0",
    });
    await finishAnimations();
    const homeURL = page.url();
    await shoot();

    // dismiss cookie banner
    await page.click("a.cc-dismiss");
    await page.waitForSelector("div.cc-banner", { hidden: true });
    await shoot();

    // about page
    await page.click("a.info");
    await page.waitForNetworkIdle(); // loads close.png and fonts
    await page.waitForSelector("aside.overlay", { visible: true });
    await finishAnimations();
    await shoot();
    await page.click("aside.overlay a.close");
    await page.waitForSelector("aside.overlay", { hidden: true });
    await finishAnimations();

    await visitProjects(homeURL);
    await visitPublications(homeURL);
  } catch (e) {
    console.log(e);
    await shoot();
  }
  await browser.close();
  console.timeEnd("time");
})();
