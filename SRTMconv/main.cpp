/*
Convert SRTM files to VisuGps format.
This application has been developped with Ultimate++ <http://www.ultimatepp.org/>

SRTM files can be found at <http://srtm.csi.cgiar.org/index.asp>

Author:
	Victor Berchet
	
History:
	Jun 18, 2007: Initial revision	
	
*/

#include "SRTMconv.h"

SRTMconv::SRTMconv()
{
	CtrlLayout(*this, "SRTM to VisuGPS converter");

	btnConvert <<= THISBACK(Convert);
	btnOpen <<= THISBACK(Open);
	
	fs.Multi(false)
	  .NoEditFileName()
      .Type("SRTM files (*.asc)", "*.asc")
      .Type("SRTMB file (*.strmb)", "*.strmb")
      .Type("All Files (*.*)", "*.*");
}


void SRTMconv::ComputeImg(void)
{
	ImageBuffer ib(previewSize, previewSize);
	
	for(int y = 0; y < previewSize; y++) {
		RGBA *l = ib[y];
		for(int x = 0; x < previewSize; x++) {			
			int step = srtmSize / previewSize;
			byte height = min(raster.At(srtmSize * step * y + step * x) / 15 + 40, 255);
			l->a = 255;
			l->r = height;
			l->g = height;
			l->b = height;
			l++;
		}
	}

	img.SetImage(ib);

}

void SRTMconv::Open(void)
{
	fs.ActiveType(0);
    if (!fs.ExecuteOpen("Select a SRTM file")) {
    	return;
    } else {        
    	txt.SetText(~fs);  
    	if (ToLower(GetFileExt(~fs)) == ".asc") {
    		btnConvert.SetLabel("Convert");
    	} else {
    		btnConvert.SetLabel("Display");
    	}
    }    			
}

void SRTMconv::StoreSrtm(void)
{
	if (raster.GetCount() == srtmSize * srtmSize) {
		
		String fName = txt.GetText();
		
		if (!IsNull(llCorner.x) && !IsNull(llCorner.y)) {
			fName = ToLower(GetFileDirectory(fName)) + 
					Format("strm3_%d_%d.strmb", llCorner.y, llCorner.x);
		} else {
			fName = ToLower(GetFileDirectory(fName) + 
							GetFileTitle(fName) + ".srtmb");		
		}
				
		FileOut f(fName);
		
		Progress progress(this, "Storing the output file...", srtmSize * srtmSize);			
		
		for (int i = 0; i < srtmSize * srtmSize; i++) {
			int val;
			if (raster[i] <= 0) {
				val = 0;
			} else {
				val = ((raster[i] + (elevFactor / 2)) / elevFactor) + 1;
				val = minmax(val, 0, 255);
			}
			f.Put(val);
			progress.Step();
		}
		
		progress.Hide();
		f.Close();
	}
}

void SRTMconv::Convert(void)
{
	String fName = txt.GetText();
	
	llCorner.SetNull();
	
	if (ToLower(GetFileExt(fName)) == ".asc") {

		if (FileExists(fName)) {
			FileIn file(fName);		
			String s;
			
			raster.Clear();
			
			// Process SRTM header 
			for (int i = 0; i < 6; i++) {				
				Vector<String> fields = Split(file.GetLine(), ' ');
				if (fields.GetCount() >= 2) {				
					if (ToUpper(fields[0]) == "XLLCORNER") {
						llCorner.x = ScanInt(fields[1]);
					} else if (ToUpper(fields[0]) == "YLLCORNER") {
						llCorner.y = ScanInt(fields[1]);
					}					
				}			
			}
					
			Progress progress(this, "Reading the input file...", srtmSize * srtmSize);					
					
			while (!file.IsEof()) {
				int b = file.Get();
				if (b == ' ') {			
					short val = StrInt(s);
					if (val < 0) val = 0;
					raster.Add(val);
					s = "";
					progress.Step();
				} else {
					s += b;					
				}
			}

			progress.Hide();
			file.Close();
			
			ComputeImg();
			StoreSrtm();
		}
	} else if (ToLower(GetFileExt(fName)) == ".srtmb") {
		
		raster.Clear();
		
		if (FileExists(fName)) {
			FileIn file(fName);
			
			Progress progress(this, "Reading the input file...", srtmSize * srtmSize);
			
			while (!file.IsEof()) {
				raster.Add(file.Get() * elevFactor);
				progress.Step();
			}
			
			progress.Hide();
			file.Close();
			
			if (raster.GetCount() == srtmSize * srtmSize) {
				ComputeImg();
			}		
		}
	}
}

GUI_APP_MAIN
{
	SRTMconv conv;
	LoadFromFile(conv);
	conv.Run();
	StoreToFile(conv);
}

